<?php

namespace App\Jobs;

use App\Models\Source;
use App\Utils\Chunker;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Process;

class ParseSourceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $sourceId;
    
    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(string $sourceId)
    {
        $this->sourceId = $sourceId;
    }

    public function handle(): void
    {
        $source = Source::findOrFail($this->sourceId);
        
        if ($source->status === 'processed') {
            Log::info('Source already processed, skipping', ['source_id' => $this->sourceId]);
            return;
        }

        try {
            $source->update(['status' => 'processing']);
            
            $text = $this->extractText($source);
            
            if (empty(trim($text))) {
                throw new \Exception('No text extracted from source');
            }

            $newContentHash = hash('sha256', $text);
            
            if ($source->content_hash === $newContentHash) {
                Log::info('Content unchanged, skipping processing', ['source_id' => $this->sourceId]);
                $source->update(['status' => 'processed']);
                return;
            }

            $chunks = Chunker::chunk(
                text: $text,
                sourceId: $this->sourceId,
                maxTokens: 700,
                overlap: 150
            );

            if (empty($chunks)) {
                throw new \Exception('No chunks created from text');
            }

            $source->update([
                'content_hash' => $newContentHash,
                'status' => 'chunked'
            ]);

            ChunkAndEmbedJob::dispatch($this->sourceId, $chunks);

            Log::info('Source parsed successfully', [
                'source_id' => $this->sourceId,
                'chunks_count' => count($chunks),
                'text_length' => strlen($text)
            ]);

        } catch (\Exception $e) {
            $source->update(['status' => 'failed']);
            Log::error('Failed to parse source', [
                'source_id' => $this->sourceId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    private function extractText(Source $source): string
    {
        $filePath = $this->getFilePath($source);
        
        if (!file_exists($filePath)) {
            throw new \Exception("Source file not found: {$filePath}");
        }

        $sourceType = strtolower($source->source_type);
        
        return match ($sourceType) {
            'txt', 'text' => $this->extractFromTxt($filePath),
            'pdf' => $this->extractFromPdf($filePath),
            default => throw new \Exception("Unsupported source type: {$sourceType}")
        };
    }

    private function getFilePath(Source $source): string
    {
        $identifier = $source->source_identifier;

        return storage_path('app/private/' . $identifier);
    }

    private function extractFromTxt(string $filePath): string
    {
        $content = file_get_contents($filePath);
        
        if ($content === false) {
            throw new \Exception("Failed to read text file: {$filePath}");
        }

        return mb_convert_encoding($content, 'UTF-8', 'auto');
    }

    private function extractFromPdf(string $filePath)
    {
        return $this->extractWithPoppler($filePath);
    }

    private function extractWithPoppler(string $filePath): string
    {
        $command = "pdftotext -layout -nopgbrk \"{$filePath}\" -";
        
        $result = Process::run($command);
        
        if (!$result->successful()) {
            throw new \Exception("Poppler extraction failed: " . $result->errorOutput());
        }

        $text = $result->output();
        
        if (empty(trim($text))) {
            throw new \Exception("No text extracted with Poppler");
        }

        return $text;
    }

    private function cleanupTempDir(string $tempDir): void
    {
        try {
            $files = glob($tempDir . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            rmdir($tempDir);
        } catch (\Exception $e) {
            Log::warning('Failed to cleanup temp directory', [
                'dir' => $tempDir,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Source::where('id', $this->sourceId)->update(['status' => 'failed']);
        
        Log::error('ParseSourceJob failed permanently', [
            'source_id' => $this->sourceId,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }
}

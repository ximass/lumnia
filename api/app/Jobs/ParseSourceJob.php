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
use Smalot\PdfParser\Parser;
use League\Csv\Reader;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\HeadingRowImport;
use PhpOffice\PhpWord\IOFactory;

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
            'csv' => $this->extractFromCsv($filePath),
            'xlsx', 'xls' => $this->extractFromExcel($filePath),
            'doc', 'docx' => $this->extractFromWord($filePath),
            'odt' => $this->extractFromOdt($filePath),
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

    private function extractFromPdf(string $filePath): string
    {
        return $this->extractWithSmalot($filePath);
    }

    private function extractWithSmalot(string $filePath): string
    {
        try {
            $parser = new Parser();
            $pdf = $parser->parseFile($filePath);
            
            $text = $pdf->getText();

            Log::info('Extracted text from PDF using Smalot', [
                'text' => $text,
                'file' => $filePath,
                'text_length' => strlen($text)
            ]);
            
            if (empty(trim($text))) {
                throw new \Exception("No text extracted from PDF");
            }

            return $text;
        } catch (\Exception $e) {
            throw new \Exception("PDF extraction failed: " . $e->getMessage());
        }
    }

    private function extractFromCsv(string $filePath): string
    {
        try {
            $csv = Reader::createFromPath($filePath, 'r');
            
            $csv->setHeaderOffset(0);
            
            $headers = $csv->getHeader();
            $records = iterator_to_array($csv->getRecords());
            
            $text = "Headers: " . implode(', ', $headers) . "\n\n";
            
            foreach ($records as $offset => $record) {
                $rowText = [];
                foreach ($record as $key => $value) {
                    if (!empty(trim($value))) {
                        $rowText[] = "$key: " . trim($value);
                    }
                }
                if (!empty($rowText)) {
                    $text .= "Row " . ($offset + 1) . ": " . implode('; ', $rowText) . "\n";
                }
            }

            Log::info('Extracted text from CSV', [
                'file' => $filePath,
                'text_length' => strlen($text),
                'rows_count' => count($records),
                'headers' => $headers
            ]);
            
            if (empty(trim($text))) {
                throw new \Exception("No text extracted from CSV");
            }

            return $text;
        } catch (\Exception $e) {
            throw new \Exception("CSV extraction failed: " . $e->getMessage());
        }
    }

    private function extractFromExcel(string $filePath): string
    {
        try {
            $headings = Excel::toArray(new HeadingRowImport, $filePath)[0];
            $data = Excel::toArray([], $filePath)[0];
            
            if (empty($data)) {
                throw new \Exception("No data found in Excel file");
            }
            
            $headers = array_shift($data);
            $text = "Headers: " . implode(', ', array_filter($headers)) . "\n\n";
            
            foreach ($data as $rowIndex => $row) {
                if (empty(array_filter($row))) continue;
                
                $rowText = [];
                foreach ($row as $colIndex => $value) {
                    $header = $headers[$colIndex] ?? "Column " . ($colIndex + 1);
                    if (!empty($value)) {
                        $rowText[] = "$header: $value";
                    }
                }
                
                if (!empty($rowText)) {
                    $text .= "Row " . ($rowIndex + 1) . ": " . implode('; ', $rowText) . "\n";
                }
            }

            Log::info('Extracted text from Excel', [
                'file' => $filePath,
                'text_length' => strlen($text),
                'rows_count' => count($data)
            ]);
            
            if (empty(trim($text))) {
                throw new \Exception("No text extracted from Excel file");
            }

            return $text;
        } catch (\Exception $e) {
            throw new \Exception("Excel extraction failed: " . $e->getMessage());
        }
    }

    private function extractFromWord(string $filePath): string
    {
        try {
            $phpWord = IOFactory::load($filePath);
            $text = '';
            
            foreach ($phpWord->getSections() as $section) {
                $text .= $this->extractTextFromContainer($section) . "\n";
            }

            Log::info('Extracted text from Word document', [
                'file' => $filePath,
                'text_length' => strlen($text)
            ]);
            
            if (empty(trim($text))) {
                throw new \Exception("No text extracted from Word document");
            }

            return trim($text);
        } catch (\Exception $e) {
            throw new \Exception("Word document extraction failed: " . $e->getMessage());
        }
    }

    private function extractFromOdt(string $filePath): string
    {
        try {
            $phpWord = IOFactory::load($filePath, 'ODText');
            $text = '';
            
            foreach ($phpWord->getSections() as $section) {
                $text .= $this->extractTextFromContainer($section) . "\n";
            }

            Log::info('Extracted text from ODT document', [
                'file' => $filePath,
                'text_length' => strlen($text)
            ]);
            
            if (empty(trim($text))) {
                throw new \Exception("No text extracted from ODT document");
            }

            return trim($text);
        } catch (\Exception $e) {
            throw new \Exception("ODT document extraction failed: " . $e->getMessage());
        }
    }

    private function extractTextFromContainer($container): string
    {
        $text = '';
        
        if (method_exists($container, 'getElements')) {
            foreach ($container->getElements() as $element) {
                $elementClass = get_class($element);
                
                switch ($elementClass) {
                    case 'PhpOffice\PhpWord\Element\Text':
                        $text .= $element->getText() . " ";
                        break;
                    case 'PhpOffice\PhpWord\Element\TextRun':
                        foreach ($element->getElements() as $textElement) {
                            if (method_exists($textElement, 'getText')) {
                                $text .= $textElement->getText() . " ";
                            }
                        }
                        break;
                    case 'PhpOffice\PhpWord\Element\TextBreak':
                        $text .= "\n";
                        break;
                    case 'PhpOffice\PhpWord\Element\ListItem':
                        $text .= "• " . $this->extractTextFromContainer($element) . "\n";
                        break;
                    default:
                        if (method_exists($element, 'getElements')) {
                            $text .= $this->extractTextFromContainer($element) . " ";
                        } elseif (method_exists($element, 'getText')) {
                            $text .= $element->getText() . " ";
                        }
                        break;
                }
            }
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

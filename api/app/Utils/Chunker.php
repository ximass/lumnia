<?php

namespace App\Utils;

use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;

class Chunker
{
    private static array $tokenCache = [];

    public static function chunk(
        string $text,
        string $sourceId,
        int $maxTokens = 700,
        int $overlap = 150
    ): array {
        if (empty($text)) {
            return [];
        }

        $tokens = self::tokenize($text);
        $chunks = [];
        $chunkIndex = 0;
        $position = 0;

        while ($position < count($tokens)) {
            $endPosition = min($position + $maxTokens, count($tokens));
            $chunkTokens = array_slice($tokens, $position, $endPosition - $position);
            
            $chunkText = self::detokenize($chunkTokens);
            $chunkId = self::generateChunkId($sourceId, $chunkIndex, $chunkText);

            $chunks[] = [
                'text' => $chunkText,
                'chunk_index' => $chunkIndex,
                'offset_tokens' => $position,
                'chunk_id' => $chunkId,
                'token_count' => count($chunkTokens),
                'source_id' => $sourceId,
            ];

            $chunkIndex++;
            
            if ($endPosition >= count($tokens)) {
                break;
            }

            $position = $endPosition - $overlap;
            
            if ($position <= 0) {
                $position = $endPosition;
            }
        }

        return $chunks;
    }

    private static function tokenize(string $text): array
    {
        $cacheKey = md5($text);
        
        if (isset(self::$tokenCache[$cacheKey])) {
            return self::$tokenCache[$cacheKey];
        }

        $tokens = self::fallbackTokenize($text);

        self::$tokenCache[$cacheKey] = $tokens;
        
        return $tokens;
    }

    private static function tokenizeWithPython(string $text): array
    {
        try {
            $pythonScript = base_path('scripts/tokenizer.py');
            
            if (!file_exists($pythonScript)) {
                self::createPythonTokenizer($pythonScript);
            }

            $escapedText = addslashes($text);
            $result = Process::run("python \"{$pythonScript}\" \"{$escapedText}\"");

            if ($result->successful()) {
                $output = trim($result->output());
                $tokens = json_decode($output, true);
                
                if (is_array($tokens)) {
                    return $tokens;
                }
            }
        } catch (\Exception $e) {
            // Fallback to simple tokenization
        }

        return [];
    }

    private static function createPythonTokenizer(string $scriptPath): void
    {
        $directory = dirname($scriptPath);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $pythonCode = <<<'PYTHON'
#!/usr/bin/env python3
import sys
import json
import re

def simple_tokenize(text):
    """Simple tokenization that approximates GPT tokenization"""
    # Basic word and punctuation tokenization
    tokens = re.findall(r'\b\w+\b|[^\w\s]', text.lower())
    
    # Approximate subword tokenization for longer words
    result = []
    for token in tokens:
        if len(token) > 10:  # Split long words
            # Simple heuristic: split every 4-6 characters
            for i in range(0, len(token), 5):
                result.append(token[i:i+5])
        else:
            result.append(token)
    
    return result

if __name__ == "__main__":
    if len(sys.argv) < 2:
        print(json.dumps([]))
        sys.exit(1)
    
    text = sys.argv[1]
    tokens = simple_tokenize(text)
    print(json.dumps(tokens))
PYTHON;

        file_put_contents($scriptPath, $pythonCode);
        chmod($scriptPath, 0755);
    }

    private static function fallbackTokenize(string $text): array
    {
        // Simple word-based tokenization as fallback
        $words = preg_split('/\s+/', trim($text));
        $tokens = [];

        foreach ($words as $word) {
            if (strlen($word) > 15) {
                // Split very long words
                $chunks = str_split($word, 8);
                $tokens = array_merge($tokens, $chunks);
            } else {
                $tokens[] = $word;
            }
        }

        return array_filter($tokens);
    }

    private static function detokenize(array $tokens): string
    {
        return implode(' ', $tokens);
    }

    private static function generateChunkId(string $sourceId, int $chunkIndex, string $text): string
    {
        $data = sprintf('%s_%d_%s', $sourceId, $chunkIndex, $text);
        return hash('sha256', $data);
    }

    public static function clearCache(): void
    {
        self::$tokenCache = [];
    }

    public static function estimateTokens(string $text): int
    {
        // Quick estimation without full tokenization
        $wordCount = str_word_count($text);
        // Approximate ratio: 1 word ≈ 1.3 tokens for English
        return (int) ceil($wordCount * 1.3);
    }

    public static function getOptimalChunkSize(int $targetTokens, int $maxTokens = 700): int
    {
        return min($targetTokens, $maxTokens);
    }

    public static function chunkJson(string $text, string $sourceId): array
    {
        return [
            [
                'source_id' => $sourceId,
                'chunk_index' => 0,
                'text' => trim($text),
                'chunk_id' => self::generateChunkId($sourceId, 0, trim($text)),
                'metadata' => [
                    'chunking_method' => 'json_full_document'
                ]
            ]
        ];
    }

    public static function chunkJsonl(string $text, string $sourceId): array
    {
        $lines = explode("\n\n---\n\n", $text);
        $chunks = [];
        
        foreach ($lines as $index => $line) {
            $line = trim($line);
            
            if (empty($line)) {
                continue;
            }

            $chunks[] = [
                'source_id' => $sourceId,
                'chunk_index' => $index,
                'text' => $line,
                'chunk_id' => self::generateChunkId($sourceId, $index, $line),
                'metadata' => [
                    'line_number' => $index + 1,
                    'chunking_method' => 'jsonl_line'
                ]
            ];
        }

        return $chunks;
    }
}

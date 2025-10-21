<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiClient
{
    protected array $config;

    public function __construct()
    {
        $this->config = config('providers.llm.gemini', []);
    }

    public function generateContent(array $messages, float $temperature, bool $stream = false, $callback = null): string
    {
        $apiKey = $this->config['api_key'] ?? '';
        $model = $this->config['model'] ?? 'gemini-2.5-flash';
        $baseUrl = $this->config['base_url'] ?? 'https://generativelanguage.googleapis.com';
        
        if (empty($apiKey)) {
            throw new \Exception('Google Gemini API key is not configured');
        }

        $contents = $this->formatMessagesForGemini($messages);
        
        $payload = [
            'contents' => $contents,
            'generationConfig' => [
                'temperature' => $temperature,
                'maxOutputTokens' => $this->config['max_tokens'] ?? 30000,
            ],
        ];

        if ($stream) {
            return $this->handleStream($apiKey, $model, $baseUrl, $payload, $callback);
        } else {
            return $this->handleStandard($apiKey, $model, $baseUrl, $payload);
        }
    }

    private function formatMessagesForGemini(array $messages): array
    {
        $contents = [];
        
        foreach ($messages as $message) {
            $role = $message['role'] === 'assistant' ? 'model' : 'user';
            
            $contents[] = [
                'role' => $role,
                'parts' => [
                    ['text' => $message['content']]
                ]
            ];
        }

        return $contents;
    }

    private function handleStandard(string $apiKey, string $model, string $baseUrl, array $payload): string
    {
        $endpoint = str_replace('{model}', $model, $this->config['chat_endpoint']);
        $url = $baseUrl . $endpoint . '?key=' . $apiKey;

        $httpClient = Http::timeout($this->config['timeout'] ?? 120);
        
        if (($this->config['verify_ssl'] ?? true) === false) {
            $httpClient = $httpClient->withOptions(['verify' => false]);
        }

        $response = $httpClient->post($url, $payload);

        if (!$response->successful()) {
            Log::error('Google Gemini API error', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            throw new \Exception('Google Gemini API request failed: ' . $response->body());
        }

        $data = $response->json();

        if (!isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            Log::error('Unexpected Gemini API response structure', ['data' => $data]);
            throw new \Exception('Unexpected response format from Google Gemini API');
        }

        return $data['candidates'][0]['content']['parts'][0]['text'];
    }

    private function handleStream(string $apiKey, string $model, string $baseUrl, array $payload, $callback = null): string
    {
        $endpoint = str_replace('{model}', $model, $this->config['stream_endpoint']);
        $url = $baseUrl . $endpoint . '?key=' . $apiKey . '&alt=sse';

        $clientOptions = [
            'json' => $payload,
            'timeout' => $this->config['timeout'] ?? 120,
            'stream' => true,
        ];
        
        if (($this->config['verify_ssl'] ?? true) === false) {
            $clientOptions['verify'] = false;
        }

        $client = new \GuzzleHttp\Client();
        $fullResponse = '';

        $response = $client->post($url, $clientOptions);

        $body = $response->getBody();
        $buffer = '';

        while (!$body->eof()) {
            $chunk = $body->read(1024);
            $buffer .= $chunk;

            $lines = explode("\n", $buffer);
            $buffer = array_pop($lines);

            foreach ($lines as $line) {
                $line = trim($line);
                
                if (empty($line) || $line === 'data: [DONE]') {
                    continue;
                }

                if (strpos($line, 'data: ') === 0) {
                    $jsonData = substr($line, 6);
                    $data = json_decode($jsonData, true);

                    if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                        $text = $data['candidates'][0]['content']['parts'][0]['text'];
                        $fullResponse .= $text;

                        if ($callback) {
                            $callback($text);
                        }
                    }
                }
            }
        }

        Log::info('Google Gemini Stream completed', [
            'response_length' => strlen($fullResponse)
        ]);

        return $fullResponse;
    }

    public function generateEmbedding(string $text): array
    {
        $apiKey = $this->config['api_key'] ?? '';
        $model = config('providers.embedding.gemini.model', 'models/embedding-001');
        $baseUrl = config('providers.embedding.gemini.base_url', 'https://generativelanguage.googleapis.com');
        
        if (empty($apiKey)) {
            throw new \Exception('Google Gemini API key is not configured');
        }

        $endpoint = str_replace('{model}', $model, config('providers.embedding.gemini.endpoint'));
        $url = $baseUrl . $endpoint . '?key=' . $apiKey;

        $payload = [
            'content' => [
                'parts' => [
                    ['text' => $text]
                ]
            ],
            'outputDimensionality' => config('providers.embedding.gemini.dimensions', 768)
        ];

        $httpClient = Http::timeout(config('providers.embedding.gemini.timeout', 120));
        
        if (config('providers.embedding.gemini.verify_ssl', true) === false) {
            $httpClient = $httpClient->withOptions(['verify' => false]);
        }

        $response = $httpClient->post($url, $payload);

        if (!$response->successful()) {
            Log::error('Google Gemini Embedding API error', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            throw new \Exception('Google Gemini Embedding API request failed: ' . $response->body());
        }

        $data = $response->json();

        if (!isset($data['embedding']['values'])) {
            Log::error('Unexpected Gemini Embedding API response structure', ['data' => $data]);
            throw new \Exception('Unexpected response format from Google Gemini Embedding API');
        }

        return $data['embedding']['values'];
    }
}

<?php

namespace App\Http\Controllers;

use App\Jobs\ParseSourceJob;
use App\Models\Source;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SourceProcessingController extends Controller
{
    public function uploadAndProcess(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'file' => [
                'required',
                'file',
                'max:10240',
                function ($attribute, $value, $fail) {
                    $allowedExtensions = ['txt', 'pdf', 'csv', 'xlsx', 'doc', 'docx', 'odt', 'json', 'jsonl'];
                    $extension = strtolower($value->getClientOriginalExtension());
                    
                    if (!in_array($extension, $allowedExtensions)) {
                        $fail('The file must be one of the following types: ' . implode(', ', $allowedExtensions));
                    }
                },
            ],
            'kb_id' => 'required|uuid|exists:knowledge_bases,id',
            'source_type' => 'sometimes|string|in:txt,pdf,csv,xlsx,doc,docx,odt,json,jsonl',
        ]);

        try {
            $file = $request->file('file');
            $extension = $file->getClientOriginalExtension();
            $sourceType = $validated['source_type'] ?? $extension;
            
            $filename = Str::uuid() . '.' . $extension;
            $filePath = $file->storeAs('sources', $filename);
            
            $source = Source::create([
                'id' => Str::uuid(),
                'kb_id' => $validated['kb_id'],
                'source_type' => $sourceType,
                'source_identifier' => $filePath,
                'content_hash' => '',
                'status' => 'uploaded',
                'metadata' => [
                    'original_filename' => $file->getClientOriginalName(),
                    'file_size' => $file->getSize(),
                    'uploaded_at' => now()->toISOString(),
                ],
            ]);

            ParseSourceJob::dispatch($source->id);

            return response()->json([
                'status' => 'success',
                'message' => 'File uploaded and processing started',
                'data' => $source->load('knowledgeBase')
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to upload and process file',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function processExisting(Request $request, string $sourceId): JsonResponse
    {
        $source = Source::findOrFail($sourceId);

        if (in_array($source->status, ['processing', 'embedding', 'chunked'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Source is already being processed'
            ], 409);
        }

        try {
            $source->update(['status' => 'queued']);
            ParseSourceJob::dispatch($sourceId);

            return response()->json([
                'status' => 'success',
                'message' => 'Processing started',
                'data' => $source->fresh()->load('knowledgeBase')
            ]);

        } catch (\Exception $e) {
            $source->update(['status' => 'failed']);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to start processing',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getStatus(string $sourceId): JsonResponse
    {
        $source = Source::with(['knowledgeBase', 'chunks'])
            ->findOrFail($sourceId);

        $chunkCount = $source->chunks->count();
        $processingInfo = $source->metadata['processing_stats'] ?? null;

        return response()->json([
            'status' => 'success',
            'data' => [
                'source' => $source,
                'chunk_count' => $chunkCount,
                'processing_info' => $processingInfo,
            ]
        ]);
    }

    public function retry(Request $request, string $sourceId): JsonResponse
    {
        $source = Source::findOrFail($sourceId);

        if (!in_array($source->status, ['failed', 'embedding_failed', 'upsert_failed'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Source is not in a failed state'
            ], 400);
        }

        try {
            $source->chunks()->delete();
            
            $source->update([
                'status' => 'retry',
                'content_hash' => '',
            ]);

            ParseSourceJob::dispatch($sourceId);

            return response()->json([
                'status' => 'success',
                'message' => 'Retry processing started',
                'data' => $source->fresh()->load('knowledgeBase')
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retry processing',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

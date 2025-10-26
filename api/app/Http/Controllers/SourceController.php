<?php

namespace App\Http\Controllers;

use App\Models\Source;
use App\Models\KnowledgeBase;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

class SourceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $kbId = $request->get('kb_id');
        
        $query = Source::with(['knowledgeBase', 'chunks']);
        
        if ($kbId) {
            $query->where('kb_id', $kbId);
        }
        
        $sources = $query->get();
        
        return response()->json([
            'status' => 'success',
            'data' => $sources
        ]);
    }

    public function create()
    {
        //
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'kb_id' => 'required|uuid|exists:knowledge_bases,id',
            'source_type' => 'required|string|max:255',
            'source_identifier' => 'required|string|max:255',
            'content_hash' => 'required|string|max:255',
            'status' => 'required|string|max:255',
            'metadata' => 'nullable|array',
        ]);

        $validated['id'] = Str::uuid();

        $source = Source::create($validated);
        $source->load(['knowledgeBase', 'chunks']);

        return response()->json([
            'status' => 'success',
            'message' => 'Source created successfully',
            'data' => $source
        ], 201);
    }

    public function show(string $id): JsonResponse
    {
        $source = Source::with(['knowledgeBase', 'chunks'])->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $source
        ]);
    }

    public function edit(string $id)
    {
        //
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $source = Source::findOrFail($id);

        $validated = $request->validate([
            'source_type' => 'sometimes|string|max:255',
            'source_identifier' => 'sometimes|string|max:255',
            'content_hash' => 'sometimes|string|max:255',
            'status' => 'sometimes|string|max:255',
            'metadata' => 'nullable|array',
        ]);

        $source->update($validated);
        $source->load(['knowledgeBase', 'chunks']);

        return response()->json([
            'status' => 'success',
            'message' => 'Source updated successfully',
            'data' => $source
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $source = Source::with('chunks')->findOrFail($id);
        
        try {
            $chunksDeleted = $source->chunks()->delete();
            
            $this->deletePhysicalFile($source);
            
            $source->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Source and associated data deleted successfully',
                'data' => [
                    'chunks_deleted' => $chunksDeleted
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete source',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function preview(string $id): JsonResponse
    {
        $source = Source::findOrFail($id);
        
        try {
            $filePath = $source->source_identifier;
            
            if (!str_starts_with($filePath, 'sources/')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid source file path'
                ], 400);
            }
            
            if (!Storage::exists($filePath)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Source file not found'
                ], 404);
            }

            $fileSize = Storage::size($filePath);
            $maxPreviewSize = 10 * 1024 * 1024;
            
            if ($fileSize > $maxPreviewSize) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'File is too large for preview. Maximum size: 10MB',
                    'data' => [
                        'file_size' => $fileSize,
                        'max_size' => $maxPreviewSize
                    ]
                ], 413);
            }

            $textTypes = ['txt', 'json', 'jsonl', 'csv', 'text'];
            $binaryTypes = ['pdf', 'doc', 'docx', 'odt'];
            $sourceType = strtolower($source->source_type);
            
            if (in_array($sourceType, $textTypes)) {
                $content = Storage::get($filePath);
                
                return response()->json([
                    'status' => 'success',
                    'data' => [
                        'source' => $source,
                        'content' => $content,
                        'file_size' => $fileSize,
                        'original_filename' => $source->metadata['original_filename'] ?? 'Unknown',
                        'preview_type' => 'text'
                    ]
                ]);
            } elseif (in_array($sourceType, $binaryTypes)) {
                $signedUrl = URL::temporarySignedRoute(
                    'source.download',
                    now()->addMinutes(30),
                    ['source' => $id, 'inline' => 'true']
                );

                return response()->json([
                    'status' => 'success',
                    'data' => [
                        'source' => $source,
                        'file_size' => $fileSize,
                        'original_filename' => $source->metadata['original_filename'] ?? 'Unknown',
                        'preview_type' => 'binary',
                        'download_url' => $signedUrl
                    ]
                ]);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Preview is not available for this file type',
                    'data' => [
                        'source_type' => $source->source_type
                    ]
                ], 400);
            }
            
        } catch (\Exception $e) {
            Log::error('Failed to preview source file', [
                'source_id' => $source->id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to preview source file',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function download(string $id)
    {
        if (!request()->hasValidSignature()) {
            abort(401, 'Invalid or expired download link');
        }

        $source = Source::findOrFail($id);
        
        try {
            $filePath = $source->source_identifier;
            
            if (!str_starts_with($filePath, 'sources/')) {
                abort(400, 'Invalid source file path');
            }
            
            if (!Storage::exists($filePath)) {
                abort(404, 'Source file not found');
            }

            $mimeTypes = [
                'pdf' => 'application/pdf',
                'doc' => 'application/msword',
                'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'odt' => 'application/vnd.oasis.opendocument.text',
                'txt' => 'text/plain',
                'json' => 'application/json',
                'jsonl' => 'application/jsonl',
                'csv' => 'text/csv'
            ];

            $extension = strtolower($source->source_type);
            $mimeType = $mimeTypes[$extension] ?? 'application/octet-stream';
            $filename = $source->metadata['original_filename'] ?? "file.{$extension}";
            
            $inline = request()->query('inline') === 'true';
            $disposition = $inline ? 'inline' : 'attachment';

            return Storage::download($filePath, $filename, [
                'Content-Type' => $mimeType,
                'Content-Disposition' => $disposition . '; filename="' . $filename . '"'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to download source file', [
                'source_id' => $source->id,
                'error' => $e->getMessage()
            ]);
            
            abort(500, 'Failed to download file');
        }
    }

    public function getDownloadUrl(string $id): JsonResponse
    {
        $source = Source::findOrFail($id);
        
        $signedUrl = URL::temporarySignedRoute(
            'source.download',
            now()->addMinutes(30),
            ['source' => $id]
        );

        return response()->json([
            'status' => 'success',
            'data' => [
                'download_url' => $signedUrl,
                'expires_at' => now()->addMinutes(30)->toIso8601String()
            ]
        ]);
    }

    public function getPreviewUrl(string $id): JsonResponse
    {
        $source = Source::findOrFail($id);
        
        $signedUrl = URL::temporarySignedRoute(
            'source.download',
            now()->addMinutes(30),
            ['source' => $id, 'inline' => 'true']
        );

        return response()->json([
            'status' => 'success',
            'data' => [
                'preview_url' => $signedUrl,
                'expires_at' => now()->addMinutes(30)->toIso8601String()
            ]
        ]);
    }

    private function deletePhysicalFile(Source $source): void
    {
        try {
            $filePath = $source->source_identifier;
            
            if (str_starts_with($filePath, 'sources/')) {
                $fullPath = storage_path('app/private/' . $filePath);
                
                if (file_exists($fullPath)) {
                    unlink($fullPath);
                }
            }
        } catch (\Exception $e) {
            Log::warning('Failed to delete physical file for source', [
                'source_id' => $source->id,
                'file_path' => $source->source_identifier,
                'error' => $e->getMessage()
            ]);
        }
    }
}

<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\FileUploadRequest;
use App\Exceptions\Custom\FileUploadException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class FileController extends Controller
{
    /**
     * Upload a file
     */
    public function upload(FileUploadRequest $request)
    {
        try {
            $file = $request->file('file');
            $type = $request->input('type', 'attachment');
            $description = $request->input('description');

            // Validate file size
            if ($file->getSize() > 10 * 1024 * 1024) { // 10MB
                throw new FileUploadException('File size exceeds 10MB limit');
            }

            // Validate file type
            $allowedMimes = [
                'image/jpeg', 'image/png', 'image/gif',
                'application/pdf',
                'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'text/plain', 'text/csv'
            ];

            if (!in_array($file->getMimeType(), $allowedMimes)) {
                throw new FileUploadException('File type not allowed');
            }

            // Generate unique filename
            $extension = $file->getClientOriginalExtension();
            $filename = Str::uuid() . '.' . $extension;
            
            // Store file based on type
            $path = match ($type) {
                'avatar' => $file->storeAs('avatars', $filename, 'public'),
                'document' => $file->storeAs('documents', $filename, 'public'),
                'attachment' => $file->storeAs('attachments', $filename, 'public'),
                default => $file->storeAs('uploads', $filename, 'public'),
            };

            if (!$path) {
                throw new FileUploadException('Failed to store file');
            }

            // Get file URL
            $url = Storage::disk('public')->url($path);

            return response()->json([
                'data' => [
                    'id' => Str::uuid(),
                    'filename' => $file->getClientOriginalName(),
                    'path' => $path,
                    'url' => $url,
                    'type' => $type,
                    'size' => $file->getSize(),
                    'mime_type' => $file->getMimeType(),
                    'description' => $description,
                    'uploaded_at' => now()->toISOString(),
                ],
                'message' => 'File uploaded successfully'
            ], 201);

        } catch (FileUploadException $e) {
            Log::error('File upload failed: ' . $e->getMessage());
            throw $e;
        } catch (\Exception $e) {
            Log::error('Unexpected file upload error: ' . $e->getMessage());
            throw new FileUploadException('File upload failed due to server error');
        }
    }

    /**
     * Get file information
     */
    public function show(Request $request, string $fileId)
    {
        // This would typically fetch file info from database
        // For now, return a placeholder response
        return response()->json([
            'data' => [
                'id' => $fileId,
                'message' => 'File information endpoint - implement based on your file storage strategy'
            ],
            'message' => 'File information retrieved successfully'
        ]);
    }

    /**
     * Delete a file
     */
    public function destroy(Request $request, string $fileId)
    {
        // This would typically delete file from storage and database
        // For now, return a placeholder response
        return response()->json([
            'message' => 'File deleted successfully'
        ]);
    }

    /**
     * List uploaded files
     */
    public function index(Request $request)
    {
        // This would typically fetch files from database
        // For now, return a placeholder response
        return response()->json([
            'data' => [],
            'message' => 'Files retrieved successfully'
        ]);
    }
}

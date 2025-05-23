<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ChunkUploadController extends Controller
{
    public function upload(Request $request)
    {
        $file = $request->file('file');
        $chunkIndex = (int)$request->input('dzchunkindex');
        $totalChunks = (int)$request->input('dztotalchunkcount');
        $fileName = $request->input('name');
        $uniqueId = $request->input('dzuuid');
        $totalFileSize = (int)$request->input('dztotalfilesize');
        $chunkSize = (int)$request->input('dzchunksize');
        $chunkByteOffset = (int)$request->input('dzchunkbyteoffset');

        // Debug information
        \Log::info('Upload request parameters:', [
            'file' => $file ? 'present' : 'missing',
            'chunkIndex' => $chunkIndex,
            'totalChunks' => $totalChunks,
            'name' => $fileName,
            'uniqueId' => $uniqueId,
            'totalFileSize' => $totalFileSize,
            'chunkSize' => $chunkSize,
            'chunkByteOffset' => $chunkByteOffset
        ]);

        // Validate required parameters
        if (!$file) {
            return response()->json([
                'message' => 'File is required',
                'status' => 'error'
            ], 400);
        }

        if (!$fileName) {
            return response()->json([
                'message' => 'File name is required',
                'status' => 'error'
            ], 400);
        }

        if (!$uniqueId) {
            return response()->json([
                'message' => 'Unique ID is required',
                'status' => 'error'
            ], 400);
        }

        // Create a temporary directory for chunks if it doesn't exist
        $tempPath = storage_path('app/chunks/' . $uniqueId);
        if (!file_exists($tempPath)) {
            mkdir($tempPath, 0777, true);
            \Log::info('Created directory: ' . $tempPath);
        }

        // Save the chunk with original extension
        $chunkFileName = sprintf('%04d.%s', $chunkIndex, pathinfo($fileName, PATHINFO_EXTENSION));
        $file->move($tempPath, $chunkFileName);

        // If this is a single chunk upload
        if ($totalChunks === 1) {
            $fileInfo = $this->mergeChunks($tempPath, $fileName, $totalChunks);
            if (isset($fileInfo['error'])) {
                return response()->json($fileInfo, 400);
            }
            return response()->json([
                'message' => 'File uploaded successfully',
                'status' => 'complete',
                'file_info' => $fileInfo
            ]);
        }

        // For multiple chunks
        $uploadedChunks = $this->getUploadedChunks($tempPath);
        $isLastChunk = $chunkIndex == $totalChunks - 1;

        // If this is the last chunk and all chunks are present, merge them
        if ($isLastChunk && count($uploadedChunks) === $totalChunks) {
            $fileInfo = $this->mergeChunks($tempPath, $fileName, $totalChunks);
            if (isset($fileInfo['error'])) {
                return response()->json($fileInfo, 400);
            }
            return response()->json([
                'message' => 'File uploaded and merged successfully',
                'status' => 'complete',
                'file_info' => $fileInfo
            ]);
        }

        return response()->json([
            'message' => 'Chunk uploaded successfully',
            'status' => 'incomplete',
            'uploaded_chunks' => count($uploadedChunks),
            'total_chunks' => $totalChunks,
            'current_chunk' => $chunkIndex
        ]);
    }

    private function getUploadedChunks($tempPath)
    {
        $files = glob($tempPath . '/*');
        $chunks = [];

        foreach ($files as $file) {
            if (is_file($file)) {
                // Extract chunk number from filename (format: 0001.ext)
                $chunkNumber = (int)explode('.', basename($file))[0];
                $chunks[$chunkNumber] = $file;
            }
        }

        ksort($chunks); // Sort chunks by number
        return $chunks;
    }

    private function mergeChunks($tempPath, $fileName, $totalChunks)
    {
        try {
            // Generate unique file ID
            $fileId = Str::uuid();
            $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
            $finalFileName = $fileId . '.' . $fileExtension;

            \Log::info('Final file name: ' . $finalFileName);

            // Create uploads directory if it doesn't exist
            $uploadPath = storage_path('app/public/uploads');
            if (!file_exists($uploadPath)) {
                mkdir($uploadPath, 0777, true);
                \Log::info('Created uploads directory: ' . $uploadPath);
            }

            // Final file path
            $finalPath = $uploadPath . '/' . $finalFileName;

            // Merge chunks
            $out = fopen($finalPath, "wb");
            if (!$out) {
                throw new \Exception("Could not create final file");
            }

            $chunks = $this->getUploadedChunks($tempPath);

            foreach ($chunks as $chunk) {
                if (is_file($chunk)) {
                    $in = fopen($chunk, "rb");
                    stream_copy_to_stream($in, $out);
                    fclose($in);
                }
            }
            fclose($out);

            // Validate file type after merging
            $mimeType = mime_content_type($finalPath);
            $allowedTypes = [
                'application/pdf',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'image/jpeg',
                'image/png',
                'image/webp'
            ];

            if (!in_array($mimeType, $allowedTypes)) {
                unlink($finalPath);
                return [
                    'error' => true,
                    'message' => 'Invalid file type. Only PDF, DOCX, XLSX, JPG, JPEG, PNG, and WEBP files are allowed.'
                ];
            }

            // Clean up chunks
            array_map('unlink', glob($tempPath . '/*'));
            rmdir($tempPath);

            return [
                'name' => $finalFileName,
                'original_name' => $fileName,
                'file_id' => $fileId,
                'path' => 'uploads/' . $finalFileName,
                'size' => filesize($finalPath),
                'mime_type' => $mimeType,
                'last_modified' => date('Y-m-d H:i:s', filemtime($finalPath)),
                'url' => Storage::url('uploads/' . $finalFileName)
            ];
        } catch (\Exception $e) {
            \Log::error('Error merging chunks: ' . $e->getMessage());
            return [
                'error' => true,
                'message' => 'Error merging file chunks: ' . $e->getMessage()
            ];
        }
    }

    public function index()
    {
        return view('chunk-upload');
    }

    public function listFiles()
    {
        return view('file-list');
    }

    public function getFiles()
    {
        // Debug storage paths
        \Log::info('Storage paths:', [
            'disk_path' => Storage::path('uploads'),
            'public_path' => public_path('storage/uploads'),
            'storage_path' => storage_path('app/public/uploads')
        ]);

        $uploadPath = public_path('storage/uploads');
        $files = glob($uploadPath . '/*');
        $fileList = [];

        foreach ($files as $file) {
            if (is_file($file)) {
                $fileList[] = [
                    'name' => basename($file),
                    'original_name' => pathinfo($file, PATHINFO_FILENAME),
                    'size' => filesize($file),
                    'mime_type' => mime_content_type($file),
                    'last_modified' => filemtime($file),
                    'url' => asset('storage/' . str_replace(public_path('storage/'), '', $file))
                ];
            }
        }

        return response()->json($fileList);
    }

    public function download($path)
    {
        if (!Storage::exists($path)) {
            abort(404);
        }

        return Storage::download($path);
    }
}

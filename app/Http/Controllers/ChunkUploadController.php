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
        $chunkNumber = $request->input('chunk');
        $totalChunks = $request->input('chunks');
        $fileName = $request->input('name');
        $uniqueId = $request->input('uniqueId');

        // Create a temporary directory for chunks if it doesn't exist
        $tempPath = storage_path('app/chunks/' . $uniqueId);
        if (!file_exists($tempPath)) {
            mkdir($tempPath, 0777, true);
        }

        // Save the chunk
        $file->move($tempPath, $chunkNumber);

        // If this is the last chunk, merge all chunks
        if ($chunkNumber == $totalChunks - 1) {
            $this->mergeChunks($tempPath, $fileName, $totalChunks);
            return response()->json(['message' => 'File uploaded and merged successfully']);
        }

        return response()->json(['message' => 'Chunk uploaded successfully']);
    }

    private function mergeChunks($tempPath, $fileName, $totalChunks)
    {
        $finalPath = storage_path('app/uploads/' . $fileName);
        $out = fopen($finalPath, "wb");

        for ($i = 0; $i < $totalChunks; $i++) {
            $in = fopen($tempPath . '/' . $i, "rb");
            stream_copy_to_stream($in, $out);
            fclose($in);
        }

        fclose($out);

        // Clean up chunks
        $this->cleanupChunks($tempPath);
    }

    private function cleanupChunks($tempPath)
    {
        $files = glob($tempPath . '/*');
        foreach ($files as $file) {
            unlink($file);
        }
        rmdir($tempPath);
    }

    public function index()
    {
        return view('chunk-upload');
    }
}

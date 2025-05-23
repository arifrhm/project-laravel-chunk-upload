<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChunkUploadController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/upload', [ChunkUploadController::class, 'index'])->name('chunk.upload.index');
Route::post('/upload', [ChunkUploadController::class, 'upload'])->name('chunk.upload');
Route::get('/files', action: [ChunkUploadController::class, 'listFiles'])->name('files.list');
Route::get('/files/download/{filename}', [ChunkUploadController::class, 'download'])->name('file.download');
Route::get('/api/files', [ChunkUploadController::class, 'getFiles'])->name('api.files.list');

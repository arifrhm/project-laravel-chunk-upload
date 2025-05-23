<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChunkUploadController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/upload', [ChunkUploadController::class, 'index'])->name('chunk.upload.index');
Route::post('/upload', [ChunkUploadController::class, 'upload'])->name('chunk.upload');

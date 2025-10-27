<?php

use App\Http\Controllers\Api\DownloadController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::middleware('throttle:120,1')->group(function () {
        Route::get('/video-info', [DownloadController::class, 'show'])->name('api.video-info');
        Route::get('/download-status/{download:download_id}', [DownloadController::class, 'status'])->name('api.download-status');
    });

    Route::middleware('throttle:30,1')->group(function () {
        Route::post('/download', [DownloadController::class, 'store'])->name('api.download');
    });
});
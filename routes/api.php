<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BookmarkController;
use App\Http\Controllers\Api\WisataController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\PengajuanController;
use App\Http\Controllers\Api\PengelolaWisataController;


// Pengelola Wisata (Protected + role pengelola)
Route::middleware(['auth:sanctum', 'pengelola'])->group(function () {
    Route::get('/pengelola/wisata', [PengelolaWisataController::class, 'index']);
    Route::post('/pengelola/wisata', [PengelolaWisataController::class, 'store']);
    Route::put('/pengelola/wisata/{id}', [PengelolaWisataController::class, 'update']);
    Route::delete('/pengelola/wisata/{id}', [PengelolaWisataController::class, 'destroy']);
    Route::post('/pengelola/wisata/{wisataId}/photos', [PengelolaWisataController::class, 'uploadPhoto']);
    Route::delete('/pengelola/wisata/{wisataId}/photos/{photoId}', [PengelolaWisataController::class, 'deletePhoto']);
    });
// Pengajuan (Protected)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/pengajuan', [PengajuanController::class, 'store']);
    Route::get('/pengajuan/status', [PengajuanController::class, 'myStatus']);
    });

// Super Admin only
Route::middleware(['auth:sanctum', 'super_admin'])->group(function () {
    Route::get('/admin/pengajuan', [PengajuanController::class, 'index']);
    Route::post('/admin/pengajuan/{id}/approve', [PengajuanController::class, 'approve']);
    Route::post('/admin/pengajuan/{id}/reject', [PengajuanController::class, 'reject']);
    });
// ✅ Public Routes (tidak butuh token)
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login',    [AuthController::class, 'login']);
    Route::get('/google',          [AuthController::class, 'redirectToGoogle']);
    Route::get('/google/callback', [AuthController::class, 'handleGoogleCallback']);
    });

// ✅ Wisata Public Routes
Route::prefix('wisata')->group(function () {
    Route::get('/',                      [WisataController::class, 'index']);
    Route::get('/{id}',                  [WisataController::class, 'show']);
    Route::get('/category/{categoryId}', [WisataController::class, 'byCategory']);
    });

Route::get('/categories', [WisataController::class, 'categories']);
// Review (Public - lihat review)
Route::get('/wisata/{wisataId}/reviews', [ReviewController::class, 'index']);

// Review (Protected)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/wisata/{wisataId}/reviews/check', [ReviewController::class, 'check']);
    Route::post('/wisata/{wisataId}/reviews', [ReviewController::class, 'store']);
    Route::delete('/wisata/{wisataId}/reviews', [ReviewController::class, 'destroy']);
    });
// ✅ Protected Routes (butuh token)
Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me',      [AuthController::class, 'me']);
    });

    // Bookmark Routes
    Route::prefix('bookmarks')->group(function () {
        Route::get('/',              [BookmarkController::class, 'index']);
        Route::post('/{wisataId}',   [BookmarkController::class, 'toggle']);
        Route::get('/{wisataId}',    [BookmarkController::class, 'check']);
    });
});
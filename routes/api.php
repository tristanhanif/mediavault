<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MediaApiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes (JWT Bearer Authentication)
|--------------------------------------------------------------------------
| Autentikasi menggunakan access token + refresh token.
| Kirim header: Authorization: Bearer {access_token}
|--------------------------------------------------------------------------
*/

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])->name('api.auth.register');
    Route::post('/login', [AuthController::class, 'login'])->name('api.auth.login');
    Route::post('/refresh', [AuthController::class, 'refresh'])
        ->middleware('jwt.is_refresh')
        ->name('api.auth.refresh');

    Route::middleware(['auth:api', 'jwt.access'])->group(function () {
        Route::get('/me', [AuthController::class, 'me'])->name('api.auth.me');
        Route::post('/logout', [AuthController::class, 'logout'])->name('api.auth.logout');
    });
});

Route::middleware(['auth:api', 'jwt.access'])->group(function () {
    Route::get('/media', [MediaApiController::class, 'index'])->name('api.media.index');
    Route::get('/media/{medium}', [MediaApiController::class, 'show'])->name('api.media.show');
    Route::delete('/media/{medium}', [MediaApiController::class, 'destroy'])->name('api.media.destroy');
});

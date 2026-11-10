<?php

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FolderController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\ShareController;
use App\Http\Controllers\TagController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Authentication (akses publik - tanpa landing page)
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->name('register.store');

    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
});

/*
|--------------------------------------------------------------------------
| Area terproteksi: seluruh aplikasi wajib login + akun aktif
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.alias');

    // Media
    Route::get('/media', [MediaController::class, 'index'])->name('media.index');
    Route::post('/media', [MediaController::class, 'store'])->name('media.store');
    Route::get('/media/trash', [MediaController::class, 'trash'])->name('media.trash');
    Route::get('/media/shared-with-me', [MediaController::class, 'sharedWithMe'])->name('media.shared');
    Route::get('/media/{medium}', [MediaController::class, 'show'])->name('media.show');
    Route::put('/media/{medium}', [MediaController::class, 'update'])->name('media.update');
    Route::delete('/media/{medium}', [MediaController::class, 'destroy'])->name('media.destroy');
    Route::post('/media/restore/{id}', [MediaController::class, 'restore'])->name('media.restore');
    Route::delete('/media/force-delete/{id}', [MediaController::class, 'forceDelete'])->name('media.force-delete');
    Route::post('/media/bulk-destroy', [MediaController::class, 'bulkDestroy'])->name('media.bulk-destroy');
    Route::post('/media/{medium}/move', [MediaController::class, 'move'])->name('media.move');

    // Streaming & unduhan privat
    Route::get('/media/{medium}/stream', [MediaController::class, 'stream'])->name('media.stream');
    Route::get('/media/{medium}/download', [MediaController::class, 'download'])->name('media.download');

    // Folder
    Route::post('/folders', [FolderController::class, 'store'])->name('folders.store');
    Route::put('/folders/{folder}', [FolderController::class, 'update'])->name('folders.update');
    Route::post('/folders/{folder}/move', [FolderController::class, 'move'])->name('folders.move');
    Route::delete('/folders/{folder}', [FolderController::class, 'destroy'])->name('folders.destroy');
    Route::post('/folders/restore/{id}', [FolderController::class, 'restore'])->name('folders.restore');
    Route::delete('/folders/force-delete/{id}', [FolderController::class, 'forceDelete'])->name('folders.force-delete');

    // Kategori
    Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
    Route::post('/categories/assign', [CategoryController::class, 'assign'])->name('categories.assign');
    Route::put('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');

    // Tag
    Route::post('/tags', [TagController::class, 'store'])->name('tags.store');
    Route::put('/tags/{tag}', [TagController::class, 'update'])->name('tags.update');
    Route::delete('/tags/{tag}', [TagController::class, 'destroy'])->name('tags.destroy');

    // Berbagi media
    Route::post('/shares', [ShareController::class, 'store'])->name('shares.store');
    Route::delete('/shares/{share}', [ShareController::class, 'revoke'])->name('shares.revoke');

    // Kelola kategori & tag
    Route::get('/settings', function () {
        return view('settings', [
            'categories' => \App\Models\Category::where('user_id', auth()->id())->orderBy('name')->get(),
            'tags' => \App\Models\Tag::where('user_id', auth()->id())->orderBy('name')->get(),
        ]);
    })->name('settings');

    /*
    |--------------------------------------------------------------------------
    | Khusus Admin
    |--------------------------------------------------------------------------
    */
    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show');
        Route::post('/users/{user}/role', [UserController::class, 'changeRole'])->name('users.role');
        Route::post('/users/{user}/toggle-active', [UserController::class, 'toggleActive'])->name('users.toggle-active');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

        Route::get('/activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');
    });
});

/*
|--------------------------------------------------------------------------
| Halaman berbagi (share link privat - wajib login)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'active'])->get('/shared/{token}', [ShareController::class, 'show'])
    ->name('shared.media');

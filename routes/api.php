<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// ── Auth Routes ────────────────────────────────────────────────
Route::prefix('auth')->group(function () {
    // Rate limit: 3 requests per 1 minute for auth endpoints
    Route::post('/register', [AuthController::class, 'register'])
        ->middleware('throttle:3,1')
        ->name('auth.register');
    
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:3,1')
        ->name('auth.login');

    Route::middleware('auth:api')->group(function () {
        Route::post('/logout',  [AuthController::class, 'logout'])->name('auth.logout');
        Route::post('/refresh', [AuthController::class, 'refresh'])->name('auth.refresh');
        Route::get('/me',       [AuthController::class, 'me'])->name('auth.me');
    });
});

// ── Product Routes ─────────────────────────────────────────────
Route::prefix('products')->group(function () {

    // Public: read-only
    Route::get('/',    [ProductController::class, 'index'])->name('products.index');
    Route::get('/{id}', [ProductController::class, 'show'])->name('products.show');

    // Protected: mutating operations (auth required)
    // Rate limit: 12 per 1 minute (1 per 5 seconds) for write operations
    Route::middleware(['auth:api'])->group(function () {
        Route::post('/', [ProductController::class, 'store'])
            ->middleware('throttle:12,1')
            ->name('products.store');
        
        Route::put('/{id}', [ProductController::class, 'update'])
            ->middleware('throttle:12,1')
            ->name('products.update');
        
        Route::delete('/{id}', [ProductController::class, 'destroy'])
            ->middleware('throttle:12,1')
            ->name('products.destroy');
    });
});

<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;
use App\Http\Middleware\RateLimitMiddleware;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// ── Auth Routes ────────────────────────────────────────────────
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])
        ->middleware(RateLimitMiddleware::make('auth', 3, 60))
        ->name('auth.register');

    Route::post('/login', [AuthController::class, 'login'])
        ->middleware(RateLimitMiddleware::make('auth', 3, 60))
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

    // Protected: mutating operations (auth + rate limit 1x/5s)
    Route::middleware(['auth:api'])->group(function () {
        Route::post('/', [ProductController::class, 'store'])
            ->middleware(RateLimitMiddleware::make('product_mutate', 1, 5))
            ->name('products.store');

        Route::put('/{id}', [ProductController::class, 'update'])
            ->middleware(RateLimitMiddleware::make('product_mutate', 1, 5))
            ->name('products.update');

        Route::delete('/{id}', [ProductController::class, 'destroy'])
            ->middleware(RateLimitMiddleware::make('product_mutate', 1, 5))
            ->name('products.destroy');
    });
});

<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BrandController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CollectionController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\WishlistController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API v1 — Sanctum-protected where noted
|--------------------------------------------------------------------------
*/
Route::prefix('v1')->group(function () {
    // Public catalog (read-only)
    Route::middleware('throttle:60,1')->group(function () {
        Route::get('products', [ProductController::class, 'index']);
        Route::get('products/{slug}', [ProductController::class, 'show']);
        Route::get('categories', [CategoryController::class, 'index']);
        Route::get('brands', [BrandController::class, 'index']);
        Route::get('collections', [CollectionController::class, 'index']);
    });

    // Auth
    Route::post('auth/token', [AuthController::class, 'issue'])->middleware('throttle:5,1');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('auth/me', [AuthController::class, 'me']);
        Route::post('auth/refresh', [AuthController::class, 'refresh']);
        Route::delete('auth/token', [AuthController::class, 'revoke']);

        Route::get('orders', [\App\Http\Controllers\Api\OrderController::class, 'index']);
        Route::get('orders/{orderNumber}', [\App\Http\Controllers\Api\OrderController::class, 'show']);

        Route::get('addresses', [\App\Http\Controllers\Api\AccountController::class, 'addresses']);

        Route::get('wishlist', [WishlistController::class, 'index']);
        Route::post('wishlist', [WishlistController::class, 'toggle']);
    });
});

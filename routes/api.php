<?php

use App\Http\Controllers\Api\Admin\DashboardController;
use App\Http\Controllers\Api\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Api\Admin\ShopController as AdminShopController;
use App\Http\Controllers\Api\Admin\ShopOwnerController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Owner\CatalogController;
use App\Http\Controllers\Api\Owner\ListingController;
use App\Http\Controllers\Api\Owner\ProductController as OwnerProductController;
use App\Http\Controllers\Api\Owner\ShopController as OwnerShopController;
use App\Http\Controllers\Api\Storefront\ProductController as StorefrontProductController;
use App\Http\Controllers\Api\Storefront\ShopController as StorefrontShopController;
use App\Models\User;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public storefront (customers, no login)
|--------------------------------------------------------------------------
| Only products assigned to an (active) shop are ever returned.
*/
Route::prefix('storefront')->name('storefront.')->group(function () {
    Route::get('shops', [StorefrontShopController::class, 'index'])->name('shops.index');
    Route::get('shops/{shop:slug}', [StorefrontShopController::class, 'show'])->name('shops.show');
    Route::get('shops/{shop:slug}/products', [StorefrontProductController::class, 'shopProducts'])->name('shops.products');
    Route::get('shops/{shop:slug}/products/{productId}', [StorefrontProductController::class, 'show'])
        ->whereNumber('productId')
        ->name('shops.products.show');
    Route::get('products', [StorefrontProductController::class, 'index'])->name('products.index');
});

/*
|--------------------------------------------------------------------------
| Authentication (admins and shop owners) – Sanctum API tokens
|--------------------------------------------------------------------------
*/
Route::post('login', [AuthController::class, 'login'])->middleware('throttle:10,1')->name('login');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('me', [AuthController::class, 'me'])->name('me');
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');

    /*
    | Admin panel
    */
    Route::middleware('role:'.User::ROLE_ADMIN)->prefix('admin')->name('admin.')->group(function () {
        Route::get('stats', DashboardController::class)->name('stats');
        Route::get('owner-options', [ShopOwnerController::class, 'options'])->name('owners.options');
        Route::apiResource('shops', AdminShopController::class);
        Route::apiResource('products', AdminProductController::class);
        Route::apiResource('owners', ShopOwnerController::class);
    });

    /*
    | Shop owner panel
    */
    Route::middleware('role:'.User::ROLE_SHOP_OWNER)->prefix('owner')->name('owner.')->group(function () {
        Route::get('shops', [OwnerShopController::class, 'index'])->name('shops.index');

        // Shared catalog
        Route::get('catalog', [CatalogController::class, 'index'])->name('catalog.index');
        Route::post('products', [OwnerProductController::class, 'store'])->name('products.store');
        Route::match(['put', 'patch'], 'products/{product}', [OwnerProductController::class, 'update'])->name('products.update');
        Route::delete('products/{product}', [OwnerProductController::class, 'destroy'])->name('products.destroy');

        // Listings in the owner's shop(s): shop-specific price, details and thumbnail
        // scopeBindings(): {listing} must belong to {shop}, otherwise 404.
        Route::scopeBindings()->group(function () {
            Route::apiResource('shops.listings', ListingController::class)->except('show');
        });
    });
});

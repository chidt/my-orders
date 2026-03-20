<?php

use App\Http\Controllers\Site\CategoryController;
use App\Http\Controllers\Site\LocationController;
use App\Http\Controllers\Site\ProductTypeController;
use App\Http\Controllers\Site\SupplierController;
use App\Http\Controllers\Site\TagController;
use App\Http\Controllers\Site\WarehouseController;
use App\Http\Controllers\SiteDashboardController;
use Illuminate\Support\Facades\Route;

// Site routes with slug prefix (auth middleware already applied in web.php)
Route::prefix('{site:slug}')->group(function () {
    // Site admin dashboard route
    Route::get('/dashboard', [SiteDashboardController::class, 'index'])
        ->name('site.dashboard');

    // Category management routes
    Route::resource('categories', CategoryController::class)->names([
        'index' => 'categories.index',
        'create' => 'categories.create',
        'store' => 'categories.store',
        'show' => 'categories.show',
        'edit' => 'categories.edit',
        'update' => 'categories.update',
        'destroy' => 'categories.destroy',
    ]);

    // Category reordering route
    Route::post('categories/reorder', [CategoryController::class, 'reorder'])
        ->name('categories.reorder');

    // Tag utility routes (must come before resource routes to avoid conflicts)
    Route::get('tags-popular', [TagController::class, 'popular'])
        ->name('tags.popular');
    Route::post('tags/merge', [TagController::class, 'merge'])
        ->name('tags.merge');
    Route::delete('tags/bulk-delete-unused', [TagController::class, 'bulkDeleteUnused'])
        ->name('tags.bulk-delete-unused');
    Route::get('tags-search', [TagController::class, 'search'])
        ->name('tags.search');

    // Tag management routes
    Route::resource('tags', TagController::class)->names([
        'index' => 'tags.index',
        'create' => 'tags.create',
        'store' => 'tags.store',
        'show' => 'tags.show',
        'edit' => 'tags.edit',
        'update' => 'tags.update',
        'destroy' => 'tags.destroy',
    ]);

    // Product Type management routes
    Route::resource('product-types', ProductTypeController::class)->names([
        'index' => 'product-types.index',
        'create' => 'product-types.create',
        'store' => 'product-types.store',
        'show' => 'product-types.show',
        'edit' => 'product-types.edit',
        'update' => 'product-types.update',
        'destroy' => 'product-types.destroy',
    ]);

    // Product Type reordering route
    Route::post('product-types/reorder', [ProductTypeController::class, 'reorder'])
        ->name('product-types.reorder');

    // Warehouse management routes
    Route::resource('warehouses', WarehouseController::class)->names([
        'index' => 'site.warehouses.index',
        'create' => 'site.warehouses.create',
        'store' => 'site.warehouses.store',
        'show' => 'site.warehouses.show',
        'edit' => 'site.warehouses.edit',
        'update' => 'site.warehouses.update',
        'destroy' => 'site.warehouses.destroy',
    ]);

    // Nested location routes within warehouse context
    Route::resource('warehouses.locations', LocationController::class)->names([
        'index' => 'site.warehouses.locations.index',
        'create' => 'site.warehouses.locations.create',
        'store' => 'site.warehouses.locations.store',
        'show' => 'site.warehouses.locations.show',
        'edit' => 'site.warehouses.locations.edit',
        'update' => 'site.warehouses.locations.update',
        'destroy' => 'site.warehouses.locations.destroy',
    ]);

    // Supplier management routes
    Route::resource('suppliers', SupplierController::class)->names([
        'index' => 'site.suppliers.index',
        'create' => 'site.suppliers.create',
        'store' => 'site.suppliers.store',
        'show' => 'site.suppliers.show',
        'edit' => 'site.suppliers.edit',
        'update' => 'site.suppliers.update',
        'destroy' => 'site.suppliers.destroy',
    ]);
});

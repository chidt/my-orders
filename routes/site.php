<?php

use App\Http\Controllers\Site\AttributeController;
use App\Http\Controllers\Site\CategoryController;
use App\Http\Controllers\Site\CustomerController;
use App\Http\Controllers\Site\LocationController;
use App\Http\Controllers\Site\OrderDetailController;
use App\Http\Controllers\Site\OrderController;
use App\Http\Controllers\Site\ProductController;
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
    Route::post('tags/quick-store', [TagController::class, 'quickStore'])
        ->name('tags.quick-store');

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

    // Product management routes
    Route::post('products/{product}/sync-child-products', [ProductController::class, 'syncChildProducts'])
        ->name('products.sync-child-products');
    Route::delete('products/{product}/child-products/{productItem}', [ProductController::class, 'destroyChildProduct'])
        ->name('products.child-products.destroy');
    Route::resource('products', ProductController::class)->names([
        'index' => 'products.index',
        'create' => 'products.create',
        'store' => 'products.store',
        'edit' => 'products.edit',
        'update' => 'products.update',
        'destroy' => 'products.destroy',
    ]);

    // Order management routes
    Route::resource('orders', OrderController::class)->names([
        'index' => 'orders.index',
        'create' => 'orders.create',
        'store' => 'orders.store',
        'show' => 'orders.show',
        'edit' => 'orders.edit',
        'update' => 'orders.update',
        'destroy' => 'orders.destroy',
    ]);
    Route::get('orders/customers/search', [OrderController::class, 'searchCustomers'])
        ->name('orders.customers.search');
    Route::get('orders/product-items/search', [OrderController::class, 'searchProductItems'])
        ->name('orders.product-items.search');
    Route::post('orders/customers/quick-store', [OrderController::class, 'quickStoreCustomer'])
        ->name('orders.customers.quick-store');
    Route::patch('orders/{order}/details/{detail}/status', [OrderController::class, 'updateDetailStatus'])
        ->name('orders.details.status.update');

    // Order detail management routes (bulk routes must be registered before {orderDetail} or "bulk" is captured as id)
    Route::get('order-details', [OrderDetailController::class, 'index'])->name('order-details.index');
    Route::patch('order-details/bulk/status', [OrderDetailController::class, 'bulkUpdateStatus'])
        ->name('order-details.bulk-status.update');
    Route::get('order-details/{orderDetail}', [OrderDetailController::class, 'show'])->name('order-details.show');
    Route::patch('order-details/{orderDetail}/status', [OrderDetailController::class, 'updateStatus'])
        ->name('order-details.status.update');
    Route::patch('order-details/{orderDetail}/payment-status', [OrderDetailController::class, 'updatePaymentStatus'])
        ->name('order-details.payment-status.update');

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

    // Attribute management routes
    Route::resource('attributes', AttributeController::class)->names([
        'index' => 'site.attributes.index',
        'create' => 'site.attributes.create',
        'store' => 'site.attributes.store',
        'show' => 'site.attributes.show',
        'edit' => 'site.attributes.edit',
        'update' => 'site.attributes.update',
        'destroy' => 'site.attributes.destroy',
    ]);

    // Customer management routes
    Route::resource('customers', CustomerController::class)->names([
        'index' => 'site.customers.index',
        'create' => 'site.customers.create',
        'store' => 'site.customers.store',
        'show' => 'site.customers.show',
        'edit' => 'site.customers.edit',
        'update' => 'site.customers.update',
        'destroy' => 'site.customers.destroy',
    ]);
});

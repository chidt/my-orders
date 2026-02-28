<?php

use App\Http\Controllers\Site\LocationController;
use App\Http\Controllers\Site\WarehouseController;
use App\Http\Controllers\SiteDashboardController;
use Illuminate\Support\Facades\Route;

// Site routes with slug prefix and authentication
Route::prefix('{site:slug}')->middleware(['auth', 'verified'])->group(function () {
    // Site admin dashboard route
    Route::get('/dashboard', [SiteDashboardController::class, 'index'])
        ->name('site.dashboard');

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
});

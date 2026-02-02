<?php

use App\Http\Controllers\SiteDashboardController;
use Illuminate\Support\Facades\Route;

// Site admin dashboard route
Route::get('/{site:slug}/dashboard', [SiteDashboardController::class, 'index'])
    ->name('site.dashboard');

// Additional site admin routes can be added here
// Example:
// Route::prefix('{site:slug}')->group(function () {
//     Route::resource('orders', SiteOrderController::class);
//     Route::resource('products', SiteProductController::class);
//     Route::resource('customers', SiteCustomerController::class);
// });

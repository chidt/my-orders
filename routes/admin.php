<?php

use App\Http\Controllers\AdminDashboardController;
use Illuminate\Support\Facades\Route;

// Group all admin routes under the 'admin' prefix
Route::prefix('admin')->group(function () {
    // Admin dashboard route
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])
        ->name('admin.dashboard'); // Keep 'admin.dashboard' name for clarity

    // Additional admin routes can be added here
    // Example:
    // Route::resource('users', AdminUserController::class);
    // Route::resource('sites', AdminSiteController::class);
});

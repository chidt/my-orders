<?php

use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\AdminDashboardController;
use Illuminate\Support\Facades\Route;

// Group all admin routes under the 'admin' prefix
Route::prefix('admin')->group(function () {
    // Admin dashboard route
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])
        ->name('admin.dashboard')
        ->middleware(['auth']);

    // Role management routes
    Route::resource('roles', RoleController::class, ['as' => 'admin']);

    // Permission management routes
    Route::resource('permissions', PermissionController::class, ['as' => 'admin']);

    // Additional admin routes can be added here
    // Example:
    // Route::resource('users', AdminUserController::class);
    // Route::resource('sites', AdminSiteController::class);
});

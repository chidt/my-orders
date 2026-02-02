<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

// Admin routes with authentication and admin role middleware
Route::middleware(['auth', 'role:admin'])->group(function () {
    require __DIR__.'/admin.php';
});

// Site admin routes with authentication and SiteAdmin role middleware
Route::middleware(['auth', 'role:SiteAdmin'])->group(function () {
    require __DIR__.'/site.php';
});

require __DIR__.'/settings.php';

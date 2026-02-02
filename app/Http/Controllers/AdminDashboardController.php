<?php

namespace App\Http\Controllers;

use App\Models\Site;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class AdminDashboardController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'total_users' => User::count(),
                'total_sites' => Site::count(),
                'admin_users' => User::role('admin')->count(),
                'site_admin_users' => User::role('SiteAdmin')->count(),
            ],
            'recent_users' => User::latest()->take(5)->get(['id', 'name', 'email', 'created_at']),
            'recent_sites' => Site::latest()->take(5)->get(['id', 'name', 'slug', 'created_at']),
        ]);
    }
}

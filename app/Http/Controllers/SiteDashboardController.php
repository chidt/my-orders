<?php

namespace App\Http\Controllers;

use App\Models\Site;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SiteDashboardController extends Controller
{
    public function index(Request $request, string $siteSlug): Response
    {
        $site = Site::where('slug', $siteSlug)->firstOrFail();

        // Ensure user can only access their own site dashboard
        if (auth()->user()->site_id !== $site->id) {
            abort(403, 'Unauthorized access to site dashboard');
        }

        return Inertia::render('site/Dashboard', [
            'site' => $site->only(['id', 'name', 'slug', 'description']),
            'stats' => [
                'site_users' => $site->users()->count(),
                'created_at' => $site->created_at->format('Y-m-d H:i:s'),
                'last_updated' => $site->updated_at->format('Y-m-d H:i:s'),
            ],
        ]);
    }
}

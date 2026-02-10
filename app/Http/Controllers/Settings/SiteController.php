<?php

namespace App\Http\Controllers\Settings;

use App\Actions\Site\UpdateSiteInformation;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateSiteRequest;
use App\Models\Site;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SiteController extends Controller
{
    use AuthorizesRequests;
    /**
     * Show the form for editing the site.
     */
    public function edit(): Response
    {
        $user = auth()->user();

        // Get user's owned sites (sites where user_id = current user id)
        $site = Site::where('user_id', $user->id)->first();

        if (! $site) {
            abort(404, 'Site not found');
        }

        $this->authorize('update', $site);

        return Inertia::render('settings/Site', [
            'site' => [
                'id' => $site->id,
                'name' => $site->name,
                'slug' => $site->slug,
                'description' => $site->description,
                'settings' => $site->settings ?? [],
            ],
        ]);
    }

    /**
     * Update the specified site in storage.
     */
    public function update(UpdateSiteRequest $request, UpdateSiteInformation $updateSiteAction): RedirectResponse
    {
        $user = auth()->user();

        // Get user's owned site
        $site = Site::where('user_id', $user->id)->firstOrFail();

        $this->authorize('update', $site);

        $validatedData = $request->validated();

        // Use the action to handle the update logic
        $updatedSite = $updateSiteAction->handle($site, $validatedData);

        return redirect()->back()
            ->with('status', 'Thông tin trang web đã được cập nhật thành công!');
    }
}

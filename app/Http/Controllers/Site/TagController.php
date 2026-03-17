<?php

namespace App\Http\Controllers\Site;

use App\Actions\Tag\CreateTag;
use App\Actions\Tag\DeleteTag;
use App\Actions\Tag\MergeTags;
use App\Actions\Tag\UpdateTag;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tag\StoreTagRequest;
use App\Http\Requests\Tag\UpdateTagRequest;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class TagController extends Controller
{
    /**
     * Display a listing of the tags.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Tag::class);

        $query = Tag::forSite(auth()->user()->site_id)
            ->withCount('products');

        // Search functionality
        if ($request->filled('search')) {
            $query->where('name', 'like', '%'.$request->search.'%');
        }

        // Filter by usage (used/unused)
        if ($request->filled('usage')) {
            if ($request->usage === 'used') {
                $query->has('products');
            } elseif ($request->usage === 'unused') {
                $query->doesntHave('products');
            }
        }

        // Sort functionality
        $sortBy = $request->get('sort_by', 'name');
        $sortDirection = $request->get('sort_direction', 'asc');

        if (in_array($sortBy, ['name', 'products_count', 'created_at'])) {
            if ($sortBy === 'products_count') {
                $query->orderBy('products_count', $sortDirection);
            } else {
                $query->orderBy($sortBy, $sortDirection);
            }
        } else {
            $query->ordered(); // Default alphabetical ordering
        }

        $tags = $query->paginate(50)->withQueryString();

        // Get usage statistics for dashboard
        $totalTags = Tag::forSite(auth()->user()->site_id)->count();
        $usedTags = Tag::forSite(auth()->user()->site_id)->has('products')->count();
        $unusedTags = $totalTags - $usedTags;

        // Get most popular tags
        $popularTags = Tag::forSite(auth()->user()->site_id)
            ->popular()
            ->limit(10)
            ->get();

        return Inertia::render('Products/Tags/Index', [
            'site' => auth()->user()->site,
            'tags' => $tags,
            'statistics' => [
                'total' => $totalTags,
                'used' => $usedTags,
                'unused' => $unusedTags,
            ],
            'popularTags' => $popularTags,
            'filters' => $request->only(['search', 'usage', 'sort_by', 'sort_direction']),
        ]);
    }

    /**
     * Show the form for creating a new tag.
     */
    public function create(): Response
    {
        Gate::authorize('create', Tag::class);

        return Inertia::render('Products/Tags/Create', [
            'site' => auth()->user()->site,
        ]);
    }

    /**
     * Store a newly created tag.
     */
    public function store(StoreTagRequest $request, CreateTag $createTag)
    {
        Gate::authorize('create', Tag::class);

        try {
            $tag = $createTag->handle($request->validated(), auth()->user()->site_id);

            return redirect()->route('tags.index', ['site' => auth()->user()->site->slug])
                ->with('success', 'Thẻ đã được tạo thành công.');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Có lỗi xảy ra: '.$e->getMessage());
        }
    }

    /**
     * Display the specified tag.
     */
    public function show($site, $tag): Response
    {
        // Manually resolve Tag if it comes as string/ID
        if (is_string($tag) || is_numeric($tag)) {
            $tag = Tag::forSite(auth()->user()->site_id)
                ->findOrFail($tag);
        }

        Gate::authorize('view', $tag);

        $tag->load(['site'])
            ->loadCount('products');

        // Get products using this tag (with pagination)
        $products = $tag->products()
            ->with(['category', 'productType'])
            ->paginate(20);

        return Inertia::render('Products/Tags/Show', [
            'site' => auth()->user()->site,
            'tag' => $tag,
            'products' => $products,
        ]);
    }

    /**
     * Show the form for editing the specified tag.
     */
    public function edit($site, $tag): Response
    {
        // Manually resolve Tag if it comes as string/ID
        if (is_string($tag) || is_numeric($tag)) {
            $tag = Tag::forSite(auth()->user()->site_id)
                ->findOrFail($tag);
        }

        Gate::authorize('update', $tag);

        return Inertia::render('Products/Tags/Edit', [
            'site' => auth()->user()->site,
            'tag' => $tag,
        ]);
    }

    /**
     * Update the specified tag.
     */
    public function update(UpdateTagRequest $request, $site, $tag, UpdateTag $updateTag)
    {
        // Manually resolve Tag if it comes as string/ID
        if (is_string($tag) || is_numeric($tag)) {
            $tag = Tag::forSite(auth()->user()->site_id)
                ->findOrFail($tag);
        }

        Gate::authorize('update', $tag);

        try {
            $updateTag->handle($tag, $request->validated());

            return redirect()->route('tags.index', ['site' => auth()->user()->site->slug])
                ->with('success', 'Thẻ đã được cập nhật thành công.');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Có lỗi xảy ra: '.$e->getMessage());
        }
    }

    /**
     * Remove the specified tag.
     */
    public function destroy($site, $tag, DeleteTag $deleteTag)
    {
        // Manually resolve Tag if it comes as string/ID
        if (is_string($tag) || is_numeric($tag)) {
            $tag = Tag::forSite(auth()->user()->site_id)
                ->findOrFail($tag);
        }

        Gate::authorize('delete', $tag);

        try {
            $deleteTag->handle($tag);

            return redirect()->route('tags.index', ['site' => auth()->user()->site->slug])
                ->with('success', 'Thẻ đã được xóa thành công.');
        } catch (\Exception $e) {
            return back()->with('error', 'Không thể xóa thẻ: '.$e->getMessage());
        }
    }

    /**
     * Get popular tags (AJAX endpoint).
     */
    public function popular(Request $request)
    {
        Gate::authorize('viewAny', Tag::class);

        $limit = $request->get('limit', 20);
        $tags = Tag::forSite(auth()->user()->site_id)
            ->popular()
            ->limit($limit)
            ->get();

        return response()->json($tags);
    }

    /**
     * Merge multiple tags into one.
     */
    public function merge(Request $request, MergeTags $mergeTags)
    {
        Gate::authorize('merge', Tag::class);

        $request->validate([
            'primary_tag_id' => 'required|exists:tags,id',
            'tag_ids' => 'required|array|min:1',
            'tag_ids.*' => 'required|exists:tags,id|different:primary_tag_id',
        ]);

        try {
            $primaryTag = Tag::forSite(auth()->user()->site_id)
                ->findOrFail($request->primary_tag_id);

            $mergeTags->handle($primaryTag, $request->tag_ids, auth()->user()->site_id);

            return back()->with('success', 'Các thẻ đã được gộp thành công.');
        } catch (\Exception $e) {
            return back()->with('error', 'Có lỗi xảy ra: '.$e->getMessage());
        }
    }

    /**
     * Bulk delete unused tags.
     */
    public function bulkDeleteUnused(Request $request, DeleteTag $deleteTag)
    {
        // Check if user can delete tags (class-level permission)
        if (! ($request->user()->can('delete_tags') || $request->user()->can('manage_tags'))) {
            abort(403);
        }

        try {
            $unusedTags = Tag::forSite(auth()->user()->site_id)
                ->doesntHave('products')
                ->get();

            $deletedCount = 0;
            foreach ($unusedTags as $tag) {
                $deleteTag->handle($tag);
                $deletedCount++;
            }

            return back()->with('success', "Đã xóa {$deletedCount} thẻ không sử dụng.");
        } catch (\Exception $e) {
            return back()->with('error', 'Có lỗi xảy ra: '.$e->getMessage());
        }
    }

    /**
     * Search tags for autocomplete (AJAX endpoint).
     */
    public function search(Request $request)
    {
        Gate::authorize('viewAny', Tag::class);

        $query = $request->get('q', '');

        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $tags = Tag::forSite(auth()->user()->site_id)
            ->where('name', 'like', '%'.$query.'%')
            ->ordered()
            ->limit(10)
            ->get(['id', 'name', 'slug']);

        return response()->json($tags);
    }
}

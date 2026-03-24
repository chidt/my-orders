<?php

namespace App\Http\Controllers\Site;

use App\Actions\Attribute\DeleteAttribute;
use App\Actions\Attribute\StoreAttribute;
use App\Actions\Attribute\UpdateAttribute;
use App\Http\Controllers\Controller;
use App\Http\Requests\Attribute\StoreAttributeRequest;
use App\Http\Requests\Attribute\UpdateAttributeRequest;
use App\Models\Attribute;
use App\Models\Site;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class AttributeController extends Controller
{
    /**
     * Display a listing of the attributes for the site.
     */
    public function index(Request $request, Site $site): Response
    {
        Gate::authorize('viewAny', [Attribute::class, $site]);

        $search = (string) $request->query('search', '');
        $sortBy = (string) $request->query('sort_by', 'order');
        $sortDirection = (string) $request->query('sort_direction', 'asc');

        $query = Attribute::query()
            ->where('site_id', $site->id)
            ->withCount('productAttributeValues');

        if ($search !== '') {
            $query->where(function ($subQuery) use ($search): void {
                $subQuery
                    ->where('name', 'like', '%'.$search.'%')
                    ->orWhere('code', 'like', '%'.$search.'%');
            });
        }

        if (in_array($sortBy, ['name', 'code', 'order', 'product_attribute_values_count', 'created_at'], true)) {
            $query->orderBy($sortBy, $sortDirection === 'desc' ? 'desc' : 'asc');
        } else {
            $query->orderBy('order')->orderBy('name');
        }

        $attributes = $query->paginate(20)->withQueryString();

        $statistics = [
            'total' => Attribute::query()->where('site_id', $site->id)->count(),
        ];

        return Inertia::render('site/attributes/Index', [
            'site' => $site->only(['id', 'name', 'slug']),
            'attributes' => $attributes,
            'statistics' => $statistics,
            'filters' => [
                'search' => $search,
                'sort_by' => $sortBy,
                'sort_direction' => $sortDirection,
            ],
        ]);
    }

    /**
     * Show the form for creating a new attribute.
     */
    public function create(Site $site): Response
    {
        Gate::authorize('create', [Attribute::class, $site]);

        return Inertia::render('site/attributes/Create', [
            'site' => $site->only(['id', 'name', 'slug']),
        ]);
    }

    /**
     * Store a newly created attribute in storage.
     */
    public function store(StoreAttributeRequest $request, Site $site, StoreAttribute $action): RedirectResponse
    {
        Gate::authorize('create', [Attribute::class, $site]);

        $action->execute($request->validated(), $site);

        return redirect()
            ->route('site.attributes.index', $site)
            ->with('success', 'Thuộc tính đã được tạo thành công.');
    }

    /**
     * Show the form for editing the specified attribute.
     */
    public function edit(Site $site, Attribute $attribute): Response
    {
        Gate::authorize('update', $attribute);

        return Inertia::render('site/attributes/Edit', [
            'site' => $site->only(['id', 'name', 'slug']),
            'attribute' => $attribute->only([
                'id',
                'name',
                'code',
                'description',
                'order',
            ]),
        ]);
    }

    /**
     * Update the specified attribute in storage.
     */
    public function update(UpdateAttributeRequest $request, Site $site, Attribute $attribute, UpdateAttribute $action): RedirectResponse
    {
        Gate::authorize('update', $attribute);

        $action->execute($attribute, $request->validated());

        return redirect()
            ->route('site.attributes.index', $site)
            ->with('success', 'Thuộc tính đã được cập nhật thành công.');
    }

    /**
     * Remove the specified attribute from storage.
     */
    public function destroy(Site $site, Attribute $attribute, DeleteAttribute $action): RedirectResponse
    {
        Gate::authorize('delete', $attribute);

        try {
            $action->execute($attribute);

            return redirect()
                ->route('site.attributes.index', $site)
                ->with('success', 'Thuộc tính đã được xóa thành công.');
        } catch (\Throwable $e) {
            return redirect()
                ->route('site.attributes.index', $site)
                ->with('error', $e->getMessage());
        }
    }
}

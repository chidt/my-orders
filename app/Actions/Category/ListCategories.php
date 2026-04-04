<?php

namespace App\Actions\Category;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Action class for listing categories with filtering, sorting, and pagination.
 *
 * This action handles the business logic for the category index page,
 * supporting various filters, search functionality, and both paginated
 * and unpaginated results for different view modes (table vs tree view).
 */
class ListCategories
{
    /**
     * Handle the category listing with filtering, sorting, and pagination.
     */
    public function handle(Request $request, int $siteId): array
    {
        $query = Category::forSite($siteId)
            ->withCount('products')
            ->with('parent');

        // Apply search functionality
        $this->applySearch($query, $request);

        // Apply parent filtering
        $this->applyParentFilter($query, $request);

        // Apply active status filter
        $this->applyStatusFilter($query, $request);

        // Apply sorting
        $this->applySorting($query, $request);

        // Handle pagination vs all results
        $categories = $this->handlePagination($query, $request);

        // Get parent categories for filter dropdown
        $parentCategories = $this->getParentCategories($siteId);

        return [
            'categories' => $categories,
            'parentCategories' => $parentCategories,
            'filters' => $request->only(['search', 'parent_id', 'is_active', 'sort_by', 'sort_direction']),
        ];
    }

    /**
     * Apply search functionality to the query.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     */
    protected function applySearch($query, Request $request): void
    {
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->search.'%')
                    ->orWhere('description', 'like', '%'.$request->search.'%');
            });
        }
    }

    /**
     * Apply parent category filtering to the query.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     */
    protected function applyParentFilter($query, Request $request): void
    {
        if ($request->filled('parent_id')) {
            if ($request->parent_id === 'root') {
                $query->roots();
            } else {
                $query->where('parent_id', $request->parent_id);
            }
        }
    }

    /**
     * Apply active status filtering to the query.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     */
    protected function applyStatusFilter($query, Request $request): void
    {
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }
    }

    /**
     * Apply sorting to the query.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     */
    protected function applySorting($query, Request $request): void
    {
        $sortBy = $request->get('sort_by', 'order');
        $sortDirection = $request->get('sort_direction', 'asc');

        if (in_array($sortBy, ['name', 'order', 'products_count', 'created_at'])) {
            if ($sortBy === 'products_count') {
                $query->orderBy('products_count', $sortDirection);
            } else {
                $query->orderBy($sortBy, $sortDirection);
            }
        } else {
            $query->ordered(); // Default ordering
        }
    }

    /**
     * Handle pagination or return all results.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return LengthAwarePaginator|object
     */
    protected function handlePagination($query, Request $request)
    {
        // Check if all categories are requested (for tree view)
        if ($request->boolean('all')) {
            $allCategories = $query->get();

            return (object) [
                'data' => $allCategories,
                'current_page' => 1,
                'last_page' => 1,
                'per_page' => $allCategories->count(),
                'total' => $allCategories->count(),
                'links' => [],
            ];
        }

        return $query->paginate(20)->withQueryString();
    }

    /**
     * Get parent categories for the filter dropdown.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function getParentCategories(int $siteId)
    {
        return Category::forSite($siteId)
            ->where(function ($query) {
                $query->roots()
                    ->orWhereHas('parent', function ($q) {
                        $q->roots();
                    });
            })
            ->with('parent')
            ->ordered()
            ->get(['id', 'name', 'parent_id']);
    }
}

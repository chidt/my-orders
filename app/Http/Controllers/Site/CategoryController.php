<?php

namespace App\Http\Controllers\Site;

use App\Actions\Category\CreateCategory;
use App\Actions\Category\DeleteCategory;
use App\Actions\Category\ListCategories;
use App\Actions\Category\ReorderCategories;
use App\Actions\Category\UpdateCategory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Category\StoreCategoryRequest;
use App\Http\Requests\Category\UpdateCategoryRequest;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    /**
     * Display a listing of the categories.
     */
    public function index(Request $request, ListCategories $listCategories): Response
    {
        Gate::authorize('viewAny', Category::class);

        $data = $listCategories->handle($request, auth()->user()->site_id);

        return Inertia::render('Products/Categories/Index', [
            'site' => auth()->user()->site,
            'categories' => $data['categories'],
            'parentCategories' => $data['parentCategories'],
            'filters' => $data['filters'],
        ]);
    }

    /**
     * Show the form for creating a new category.
     */
    public function create(): Response
    {
        Gate::authorize('create', Category::class);

        // Get potential parent categories (max 2 levels deep)
        $parentCategories = Category::forSite(auth()->user()->site_id)
            ->where(function ($query) {
                $query->roots()
                    ->orWhereHas('parent', function ($q) {
                        $q->roots();
                    });
            })
            ->with('parent')
            ->ordered()
            ->get()
            ->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->parent
                        ? $category->parent->name.' > '.$category->name
                        : $category->name,
                    'depth' => $category->depth,
                ];
            });

        return Inertia::render('Products/Categories/Create', [
            'site' => auth()->user()->site,
            'parentCategories' => $parentCategories,
        ]);
    }

    /**
     * Store a newly created category.
     */
    public function store(StoreCategoryRequest $request, CreateCategory $createCategory)
    {
        Gate::authorize('create', Category::class);

        try {
            $category = $createCategory->handle($request->validated(), auth()->user()->site_id);

            return redirect()->route('categories.index', ['site' => auth()->user()->site->slug])
                ->with('success', 'Danh mục đã được tạo thành công.');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Có lỗi xảy ra: '.$e->getMessage());
        }
    }

    /**
     * Display the specified category.
     */
    public function show($site, $category): Response
    {
        // Manually resolve Category if it comes as string/ID
        if (is_string($category) || is_numeric($category)) {
            $category = Category::forSite(auth()->user()->site_id)
                ->findOrFail($category);
        }

        Gate::authorize('view', $category);

        $category->load(['site', 'parent', 'children.children'])
            ->loadCount('products');

        return Inertia::render('Products/Categories/Show', [
            'site' => auth()->user()->site,
            'category' => $category,
        ]);
    }

    /**
     * Show the form for editing the specified category.
     */
    public function edit($site, $category): Response
    {
        // Manually resolve Category if it comes as string/ID
        if (is_string($category) || is_numeric($category)) {
            $category = Category::forSite(auth()->user()->site_id)
                ->findOrFail($category);
        }

        Gate::authorize('update', $category);

        // Get potential parent categories (excluding self and descendants)
        $parentCategories = Category::forSite(auth()->user()->site_id)
            ->where('id', '!=', $category->id)
            ->where(function ($query) {
                $query->roots()
                    ->orWhereHas('parent', function ($q) {
                        $q->roots();
                    });
            })
            ->with('parent')
            ->ordered()
            ->get()
            ->filter(function ($potentialParent) use ($category) {
                // Exclude descendants to prevent circular references
                return ! $potentialParent->isDescendantOf($category);
            })
            ->map(function ($cat) {
                return [
                    'id' => $cat->id,
                    'name' => $cat->parent
                        ? $cat->parent->name.' > '.$cat->name
                        : $cat->name,
                    'depth' => $cat->depth,
                ];
            })
            ->values();

        return Inertia::render('Products/Categories/Edit', [
            'site' => auth()->user()->site,
            'category' => $category,
            'parentCategories' => $parentCategories,
        ]);
    }

    /**
     * Update the specified category.
     */
    public function update(UpdateCategoryRequest $request, $site, $category, UpdateCategory $updateCategory)
    {
        // Manually resolve Category if it comes as string/ID
        if (is_string($category) || is_numeric($category)) {
            $category = Category::forSite(auth()->user()->site_id)
                ->findOrFail($category);
        }

        Gate::authorize('update', $category);

        try {
            $updateCategory->handle($category, $request->validated());

            return redirect()->route('categories.index', ['site' => auth()->user()->site->slug])
                ->with('success', 'Danh mục đã được cập nhật thành công.');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Có lỗi xảy ra: '.$e->getMessage());
        }
    }

    /**
     * Remove the specified category.
     */
    public function destroy($site, $category, DeleteCategory $deleteCategory)
    {
        // Manually resolve Category if it comes as string/ID
        if (is_string($category) || is_numeric($category)) {
            $category = Category::forSite(auth()->user()->site_id)
                ->findOrFail($category);
        }

        Gate::authorize('delete', $category);

        try {
            $deleteCategory->handle($category);

            return redirect()->route('categories.index', ['site' => auth()->user()->site->slug])
                ->with('success', 'Danh mục đã được xóa thành công.');
        } catch (\Exception $e) {
            return back()->with('error', 'Không thể xóa danh mục: '.$e->getMessage());
        }
    }

    /**
     * Reorder categories via drag & drop.
     */
    public function reorder(Request $request, ReorderCategories $reorderCategories)
    {
        Gate::authorize('reorder', Category::class);

        $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|exists:categories,id',
            'items.*.order' => 'required|integer|min:0',
            'items.*.parent_id' => 'nullable|exists:categories,id',
        ]);

        try {
            $reorderCategories->handle($request->items, auth()->user()->site_id);

            return response()->json(['message' => 'Thứ tự danh mục đã được cập nhật thành công.']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Có lỗi xảy ra: '.$e->getMessage()], 500);
        }
    }
}

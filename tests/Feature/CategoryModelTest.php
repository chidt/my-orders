<?php

use App\Models\Category;
use App\Models\Site;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

describe('Category Model', function () {
    it('can create a category with required fields', function () {
        $site = Site::factory()->create();

        $category = Category::create([
            'name' => 'Test Category',
            'site_id' => $site->id,
            'is_active' => true, // Explicitly set for test
        ]);

        expect($category->name)->toBe('Test Category')
            ->and($category->site_id)->toBe($site->id)
            ->and($category->slug)->toBe('test-category')
            ->and($category->is_active)->toBeTrue()
            ->and($category->order)->toBe(0);
    });

    it('auto-generates slug from name', function () {
        $site = Site::factory()->create();

        $category = Category::create([
            'name' => 'Premium Electronics & Gadgets',
            'site_id' => $site->id,
        ]);

        expect($category->slug)->toBe('premium-electronics-gadgets');
    });

    it('uses provided slug if given', function () {
        $site = Site::factory()->create();

        $category = Category::create([
            'name' => 'Test Category',
            'slug' => 'custom-slug',
            'site_id' => $site->id,
        ]);

        expect($category->slug)->toBe('custom-slug');
    });
});

describe('Category Relationships', function () {
    it('belongs to a site', function () {
        $site = Site::factory()->create();
        $category = Category::factory()->forSite($site)->create();

        expect($category->site)->toBeInstanceOf(Site::class)
            ->and($category->site->id)->toBe($site->id);
    });

    it('can have a parent category', function () {
        $site = Site::factory()->create();
        $parent = Category::factory()->forSite($site)->create();
        $child = Category::factory()->forSite($site)->create(['parent_id' => $parent->id]);

        expect($child->parent)->toBeInstanceOf(Category::class)
            ->and($child->parent->id)->toBe($parent->id);
    });

    it('can have children categories', function () {
        $site = Site::factory()->create();
        $parent = Category::factory()->forSite($site)->create();
        $child1 = Category::factory()->forSite($site)->create(['parent_id' => $parent->id]);
        $child2 = Category::factory()->forSite($site)->create(['parent_id' => $parent->id]);

        expect($parent->children)->toHaveCount(2)
            ->and($parent->children->pluck('id'))->toContain($child1->id, $child2->id);
    });
});

describe('Category Scopes', function () {
    it('scopes categories to specific site', function () {
        $site1 = Site::factory()->create();
        $site2 = Site::factory()->create();

        Category::factory()->forSite($site1)->count(3)->create();
        Category::factory()->forSite($site2)->count(2)->create();

        $site1Categories = Category::forSite($site1->id)->get();
        $site2Categories = Category::forSite($site2->id)->get();

        expect($site1Categories)->toHaveCount(3)
            ->and($site2Categories)->toHaveCount(2);
    });

    it('scopes to root categories only', function () {
        $site = Site::factory()->create();
        $parent = Category::factory()->forSite($site)->create();
        Category::factory()->forSite($site)->create(['parent_id' => $parent->id]);
        Category::factory()->forSite($site)->create(); // Another root

        $rootCategories = Category::forSite($site->id)->roots()->get();

        expect($rootCategories)->toHaveCount(2);
    });

    it('scopes to active categories only', function () {
        $site = Site::factory()->create();
        Category::factory()->forSite($site)->active()->count(2)->create();
        Category::factory()->forSite($site)->inactive()->count(3)->create();

        $activeCategories = Category::forSite($site->id)->active()->get();

        expect($activeCategories)->toHaveCount(2);
    });

    it('orders categories properly', function () {
        $site = Site::factory()->create();
        Category::factory()->forSite($site)->create(['name' => 'Z Category', 'order' => 3]);
        Category::factory()->forSite($site)->create(['name' => 'A Category', 'order' => 1]);
        Category::factory()->forSite($site)->create(['name' => 'M Category', 'order' => 2]);

        $orderedCategories = Category::forSite($site->id)->ordered()->get();

        expect($orderedCategories->first()->name)->toBe('A Category')
            ->and($orderedCategories->last()->name)->toBe('Z Category');
    });
});

describe('Category Tree Operations', function () {
    it('calculates depth correctly', function () {
        $site = Site::factory()->create();
        $level1 = Category::factory()->forSite($site)->create();
        $level2 = Category::factory()->forSite($site)->create(['parent_id' => $level1->id]);
        $level3 = Category::factory()->forSite($site)->create(['parent_id' => $level2->id]);

        expect($level1->depth)->toBe(0)
            ->and($level2->depth)->toBe(1)
            ->and($level3->depth)->toBe(2);
    });

    it('gets ancestors correctly', function () {
        $site = Site::factory()->create();
        $grandparent = Category::factory()->forSite($site)->create(['name' => 'Grandparent']);
        $parent = Category::factory()->forSite($site)->create(['name' => 'Parent', 'parent_id' => $grandparent->id]);
        $child = Category::factory()->forSite($site)->create(['name' => 'Child', 'parent_id' => $parent->id]);

        $ancestors = $child->ancestors();

        expect($ancestors)->toHaveCount(2);
        expect($ancestors->pluck('name')->toArray())->toBe(['Grandparent', 'Parent']);
    });

    it('gets breadcrumb correctly', function () {
        $site = Site::factory()->create();
        $grandparent = Category::factory()->forSite($site)->create(['name' => 'Electronics']);
        $parent = Category::factory()->forSite($site)->create(['name' => 'Smartphones', 'parent_id' => $grandparent->id]);
        $child = Category::factory()->forSite($site)->create(['name' => 'iPhone', 'parent_id' => $parent->id]);

        $breadcrumb = $child->breadcrumb;

        expect($breadcrumb)->toBe(['Electronics', 'Smartphones', 'iPhone']);
    });

    it('gets descendants correctly', function () {
        $site = Site::factory()->create();
        $parent = Category::factory()->forSite($site)->create();
        $child1 = Category::factory()->forSite($site)->create(['parent_id' => $parent->id]);
        $child2 = Category::factory()->forSite($site)->create(['parent_id' => $parent->id]);
        $grandchild = Category::factory()->forSite($site)->create(['parent_id' => $child1->id]);

        $descendants = $parent->descendants();

        expect($descendants)->toHaveCount(3);
        expect($descendants->pluck('id'))->toContain($child1->id, $child2->id, $grandchild->id);
    });

    it('detects descendant relationships correctly', function () {
        $site = Site::factory()->create();
        $grandparent = Category::factory()->forSite($site)->create();
        $parent = Category::factory()->forSite($site)->create(['parent_id' => $grandparent->id]);
        $child = Category::factory()->forSite($site)->create(['parent_id' => $parent->id]);
        $unrelated = Category::factory()->forSite($site)->create();

        expect($child->isDescendantOf($grandparent))->toBeTrue();
        expect($child->isDescendantOf($parent))->toBeTrue();
        expect($parent->isDescendantOf($grandparent))->toBeTrue();
        expect($child->isDescendantOf($unrelated))->toBeFalse();
        expect($grandparent->isDescendantOf($child))->toBeFalse();
    });
});

describe('Category Validation', function () {
    it('determines if category can be deleted when empty', function () {
        $site = Site::factory()->create();
        $category = Category::factory()->forSite($site)->create();

        expect($category->canDelete())->toBeTrue();
    });

    it('determines category cannot be deleted when has children', function () {
        $site = Site::factory()->create();
        $parent = Category::factory()->forSite($site)->create();
        Category::factory()->forSite($site)->create(['parent_id' => $parent->id]);

        expect($parent->canDelete())->toBeFalse();
    });
});

describe('Category Casts and Attributes', function () {
    it('casts order as integer', function () {
        $site = Site::factory()->create();
        $category = Category::factory()->forSite($site)->create(['order' => '5']);

        expect($category->order)->toBeInt();
        expect($category->order)->toBe(5);
    });

    it('casts is_active as boolean', function () {
        $site = Site::factory()->create();
        $category = Category::factory()->forSite($site)->create(['is_active' => 1]);

        expect($category->is_active)->toBeBool();
        expect($category->is_active)->toBeTrue();
    });

    it('casts parent_id as integer when not null', function () {
        $site = Site::factory()->create();
        $parent = Category::factory()->forSite($site)->create();
        $child = Category::factory()->forSite($site)->create(['parent_id' => (string) $parent->id]);

        expect($child->parent_id)->toBeInt();
        expect($child->parent_id)->toBe($parent->id);
    });
});

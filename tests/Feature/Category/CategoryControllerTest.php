<?php

use App\Models\Category;
use App\Models\Site;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    // Create permissions
    $permissions = [
        'manage_categories', 'view_categories', 'create_categories', 'update_categories', 'delete_categories',
    ];

    foreach ($permissions as $permission) {
        \Spatie\Permission\Models\Permission::firstOrCreate(['name' => $permission]);
    }

    // Create SiteAdmin role
    $siteAdminRole = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'SiteAdmin']);
    $siteAdminRole->syncPermissions($permissions);

    $this->site = Site::factory()->create();
    $this->user = User::factory()->create(['site_id' => $this->site->id]);
    $this->user->assignRole('SiteAdmin');
    $this->actingAs($this->user);
});

describe('CategoryController Show Method', function () {
    it('can show a category with its details', function () {
        $parentCategory = Category::factory()->forSite($this->site)->create(['name' => 'Parent Category']);
        $category = Category::factory()->forSite($this->site)->create([
            'name' => 'Test Category',
            'parent_id' => $parentCategory->id,
            'description' => 'Test Description',
        ]);

        // Create child categories
        $childCategory1 = Category::factory()->forSite($this->site)->create([
            'parent_id' => $category->id,
            'name' => 'Child 1',
        ]);
        $childCategory2 = Category::factory()->forSite($this->site)->create([
            'parent_id' => $category->id,
            'name' => 'Child 2',
        ]);

        $response = $this->get(route('categories.show', [
            'site' => $this->site->slug,
            'category' => $category->id,
        ]));

        $response->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page->component('Products/Categories/Show')
                ->has('category')
                ->where('category.id', $category->id)
                ->where('category.name', 'Test Category')
                ->where('category.description', 'Test Description')
                ->has('category.parent')
                ->has('category.children')
                ->has('site')
                ->where('site.id', $this->site->id)
            );
    });

    it('requires authorization to view a category', function () {
        // Create a user without permissions
        $unauthorizedUser = User::factory()->create(['site_id' => $this->site->id]);
        $this->actingAs($unauthorizedUser);

        $category = Category::factory()->forSite($this->site)->create();

        $response = $this->get(route('categories.show', [
            'site' => $this->site->slug,
            'category' => $category->id,
        ]));

        $response->assertForbidden();
    });

    it('prevents viewing categories from other sites', function () {
        $otherSite = Site::factory()->create();
        $otherCategory = Category::factory()->forSite($otherSite)->create();

        $response = $this->get(route('categories.show', [
            'site' => $this->site->slug,
            'category' => $otherCategory->id,
        ]));

        $response->assertNotFound();
    });

    it('returns 404 for non-existent category', function () {
        $response = $this->get(route('categories.show', [
            'site' => $this->site->slug,
            'category' => 999999,
        ]));

        $response->assertNotFound();
    });

    it('loads category with products count', function () {
        $category = Category::factory()->forSite($this->site)->create();

        $response = $this->get(route('categories.show', [
            'site' => $this->site->slug,
            'category' => $category->id,
        ]));

        $response->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page->component('Products/Categories/Show')
                ->has('category')
                ->whereType('category.products_count', 'integer')
            );
    });
});

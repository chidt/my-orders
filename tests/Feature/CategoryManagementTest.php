<?php

use App\Models\Category;
use App\Models\Site;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    // Create permissions first
    $permissions = [
        'manage_categories', 'view_categories', 'create_categories', 'update_categories', 'delete_categories', 'reorder_categories',
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

describe('Category Index', function () {
    it('can display categories index page', function () {
        Category::factory()->forSite($this->site)->count(3)->create();

        $response = $this->get(route('categories.index', ['site' => $this->site->slug]));

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Products/Categories/Index')
                ->has('categories.data', 3)
                ->has('site')
                ->has('parentCategories')
            );
    });

    it('can search categories', function () {
        Category::factory()->forSite($this->site)->create(['name' => 'Electronics']);
        Category::factory()->forSite($this->site)->create(['name' => 'Clothing']);

        $response = $this->get(route('categories.index', [
            'site' => $this->site->slug,
            'search' => 'Electronics',
        ]));

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Products/Categories/Index')
                ->has('categories.data', 1)
                ->where('categories.data.0.name', 'Electronics')
            );
    });

    it('can filter categories by parent', function () {
        $parent = Category::factory()->forSite($this->site)->create(['name' => 'Parent']);
        Category::factory()->forSite($this->site)->create(['parent_id' => $parent->id]);
        Category::factory()->forSite($this->site)->create(); // Another root category

        $response = $this->get(route('categories.index', [
            'site' => $this->site->slug,
            'parent_id' => 'root',
        ]));

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('categories.data', 2) // Both root categories
            );
    });

    it('enforces site isolation', function () {
        $otherSite = Site::factory()->create();
        Category::factory()->forSite($otherSite)->create(['name' => 'Other Site Category']);
        Category::factory()->forSite($this->site)->create(['name' => 'My Site Category']);

        $response = $this->get(route('categories.index', ['site' => $this->site->slug]));

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('categories.data', 1)
                ->where('categories.data.0.name', 'My Site Category')
            );
    });
});

describe('Category Create', function () {
    it('can display create category form', function () {
        $response = $this->get(route('categories.create', ['site' => $this->site->slug]));

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Products/Categories/Create')
                ->has('site')
                ->has('parentCategories')
            );
    });

    it('can create a new category', function () {
        $categoryData = [
            'name' => 'Test Category',
            'description' => 'Test Description',
            'order' => 10,
            'is_active' => true,
        ];

        $response = $this->post(route('categories.store', ['site' => $this->site->slug]), $categoryData);

        $response->assertRedirect(route('categories.index', ['site' => $this->site->slug]))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('categories', [
            'name' => 'Test Category',
            'site_id' => $this->site->id,
            'slug' => 'test-category',
        ]);
    });

    it('can create a category with parent', function () {
        $parent = Category::factory()->forSite($this->site)->create();

        $categoryData = [
            'name' => 'Child Category',
            'parent_id' => $parent->id,
            'order' => 1,
            'is_active' => true,
        ];

        $response = $this->post(route('categories.store', ['site' => $this->site->slug]), $categoryData);

        $response->assertRedirect(route('categories.index', ['site' => $this->site->slug]));

        $this->assertDatabaseHas('categories', [
            'name' => 'Child Category',
            'parent_id' => $parent->id,
            'site_id' => $this->site->id,
        ]);
    });

    it('validates required fields', function () {
        $response = $this->post(route('categories.store', ['site' => $this->site->slug]), []);

        $response->assertSessionHasErrors(['name']);
    });

    it('validates unique name within site', function () {
        Category::factory()->forSite($this->site)->create(['name' => 'Existing Category']);

        $response = $this->post(route('categories.store', ['site' => $this->site->slug]), [
            'name' => 'Existing Category',
        ]);

        $response->assertSessionHasErrors(['name']);
    });

    it('prevents creating categories deeper than 3 levels', function () {
        $level1 = Category::factory()->forSite($this->site)->create();
        $level2 = Category::factory()->forSite($this->site)->create(['parent_id' => $level1->id]);
        $level3 = Category::factory()->forSite($this->site)->create(['parent_id' => $level2->id]);

        // This should fail - trying to create level 4 (depth 3)
        $response = $this->post(route('categories.store', ['site' => $this->site->slug]), [
            'name' => 'Level 4 Category',
            'parent_id' => $level3->id,
        ]);

        $response->assertSessionHasErrors();
    });
});

describe('Category Update', function () {
    it('can display edit category form', function () {
        $category = Category::factory()->forSite($this->site)->create();

        $response = $this->get(route('categories.edit', [
            'site' => $this->site->slug,
            'category' => $category->id,
        ]));

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Products/Categories/Edit')
                ->has('category')
                ->has('parentCategories')
            );
    });

    it('can update a category', function () {
        $category = Category::factory()->forSite($this->site)->create([
            'name' => 'Original Name',
        ]);

        $updateData = [
            'name' => 'Updated Name',
            'description' => 'Updated Description',
            'is_active' => false,
        ];

        $response = $this->put(route('categories.update', [
            'site' => $this->site->slug,
            'category' => $category->id,
        ]), $updateData);

        $response->assertRedirect(route('categories.index', ['site' => $this->site->slug]));

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Updated Name',
            'is_active' => false,
        ]);
    });

    it('prevents circular parent references', function () {
        $parent = Category::factory()->forSite($this->site)->create();
        $child = Category::factory()->forSite($this->site)->create(['parent_id' => $parent->id]);

        $response = $this->put(route('categories.update', [
            'site' => $this->site->slug,
            'category' => $parent->id,
        ]), [
            'name' => $parent->name,
            'parent_id' => $child->id, // Circular reference
        ]);

        $response->assertSessionHasErrors();
    });

    it('enforces site ownership in updates', function () {
        $otherSite = Site::factory()->create();
        $otherCategory = Category::factory()->forSite($otherSite)->create();

        $response = $this->put(route('categories.update', [
            'site' => $this->site->slug,
            'category' => $otherCategory->id,
        ]), [
            'name' => 'Hacked Name',
        ]);

        $response->assertNotFound();
    });
});

describe('Category Delete', function () {
    it('can delete empty category', function () {
        $category = Category::factory()->forSite($this->site)->create();

        $response = $this->delete(route('categories.destroy', [
            'site' => $this->site->slug,
            'category' => $category->id,
        ]));

        $response->assertRedirect(route('categories.index', ['site' => $this->site->slug]));

        $this->assertDatabaseMissing('categories', [
            'id' => $category->id,
        ]);
    });

    it('prevents deleting category with children', function () {
        $parent = Category::factory()->forSite($this->site)->create();
        Category::factory()->forSite($this->site)->create(['parent_id' => $parent->id]);

        $response = $this->delete(route('categories.destroy', [
            'site' => $this->site->slug,
            'category' => $parent->id,
        ]));

        $response->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('categories', [
            'id' => $parent->id,
        ]);
    });

    it('enforces site ownership in deletion', function () {
        $otherSite = Site::factory()->create();
        $otherCategory = Category::factory()->forSite($otherSite)->create();

        $response = $this->delete(route('categories.destroy', [
            'site' => $this->site->slug,
            'category' => $otherCategory->id,
        ]));

        $response->assertNotFound();
    });
});

describe('Category Permissions', function () {
    it('requires authentication', function () {
        auth()->logout();

        $response = $this->get(route('categories.index', ['site' => $this->site->slug]));

        $response->assertRedirect(route('login'));
    });

    it('requires proper permissions', function () {
        $userWithoutPermissions = User::factory()->create(['site_id' => $this->site->id]);
        $this->actingAs($userWithoutPermissions);

        $response = $this->get(route('categories.index', ['site' => $this->site->slug]));

        $response->assertForbidden();
    });
});

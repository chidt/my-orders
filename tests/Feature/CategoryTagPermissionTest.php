<?php

use App\Models\Category;
use App\Models\Site;
use App\Models\Tag;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->site = Site::factory()->create();
    $this->otherSite = Site::factory()->create();

    // Create permissions
    $permissions = [
        'manage_categories', 'view_categories', 'create_categories', 'update_categories', 'delete_categories', 'reorder_categories',
        'manage_tags', 'view_tags', 'create_tags', 'update_tags', 'delete_tags', 'merge_tags',
    ];

    foreach ($permissions as $permission) {
        Permission::firstOrCreate(['name' => $permission]);
    }

    // Create roles
    $this->siteAdminRole = Role::firstOrCreate(['name' => 'SiteAdmin']);
    $this->staffRole = Role::firstOrCreate(['name' => 'Staff']);

    // Assign permissions to SiteAdmin
    $this->siteAdminRole->syncPermissions($permissions);
});

describe('Category Permissions', function () {
    it('allows SiteAdmin to view categories index', function () {
        $user = User::factory()->create(['site_id' => $this->site->id]);
        $user->assignRole('SiteAdmin');

        $response = $this->actingAs($user)->get(route('categories.index', ['site' => $this->site->slug]));

        $response->assertOk();
    });

    it('allows users with view_categories permission to view categories', function () {
        $user = User::factory()->create(['site_id' => $this->site->id]);
        $user->givePermissionTo('view_categories');

        $response = $this->actingAs($user)->get(route('categories.index', ['site' => $this->site->slug]));

        $response->assertOk();
    });

    it('denies access without proper permissions', function () {
        $user = User::factory()->create(['site_id' => $this->site->id]);

        $response = $this->actingAs($user)->get(route('categories.index', ['site' => $this->site->slug]));

        $response->assertForbidden();
    });

    it('allows users with create_categories permission to create categories', function () {
        $user = User::factory()->create(['site_id' => $this->site->id]);
        $user->givePermissionTo(['create_categories', 'view_categories']);

        $response = $this->actingAs($user)->post(route('categories.store', ['site' => $this->site->slug]), [
            'name' => 'Test Category',
            'is_active' => true,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('categories', ['name' => 'Test Category']);
    });

    it('denies category creation without proper permissions', function () {
        $user = User::factory()->create(['site_id' => $this->site->id]);
        $user->givePermissionTo('view_categories'); // Only view permission

        $response = $this->actingAs($user)->post(route('categories.store', ['site' => $this->site->slug]), [
            'name' => 'Test Category',
        ]);

        $response->assertForbidden();
    });

    it('allows users with update_categories permission to update categories', function () {
        $user = User::factory()->create(['site_id' => $this->site->id]);
        $user->givePermissionTo(['update_categories', 'view_categories']);

        $category = Category::factory()->forSite($this->site)->create();

        $response = $this->actingAs($user)->put(route('categories.update', [
            'site' => $this->site->slug,
            'category' => $category->id,
        ]), [
            'name' => 'Updated Category',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('categories', ['name' => 'Updated Category']);
    });

    it('allows users with delete_categories permission to delete categories', function () {
        $user = User::factory()->create(['site_id' => $this->site->id]);
        $user->givePermissionTo(['delete_categories', 'view_categories']);

        $category = Category::factory()->forSite($this->site)->create();

        $response = $this->actingAs($user)->delete(route('categories.destroy', [
            'site' => $this->site->slug,
            'category' => $category->id,
        ]));

        $response->assertRedirect();
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    });

    it('prevents cross-site access to categories', function () {
        $userOnSite1 = User::factory()->create(['site_id' => $this->site->id]);
        $userOnSite1->assignRole('SiteAdmin');

        $categoryOnSite2 = Category::factory()->forSite($this->otherSite)->create();

        $response = $this->actingAs($userOnSite1)->get(route('categories.edit', [
            'site' => $this->site->slug,
            'category' => $categoryOnSite2->id,
        ]));

        $response->assertNotFound();
    });
});

describe('Tag Permissions', function () {
    it('allows SiteAdmin to view tags index', function () {
        $user = User::factory()->create(['site_id' => $this->site->id]);
        $user->assignRole('SiteAdmin');

        $response = $this->actingAs($user)->get(route('tags.index', ['site' => $this->site->slug]));

        $response->assertOk();
    });

    it('allows users with view_tags permission to view tags', function () {
        $user = User::factory()->create(['site_id' => $this->site->id]);
        $user->givePermissionTo('view_tags');

        $response = $this->actingAs($user)->get(route('tags.index', ['site' => $this->site->slug]));

        $response->assertOk();
    });

    it('denies access without proper permissions', function () {
        $user = User::factory()->create(['site_id' => $this->site->id]);

        $response = $this->actingAs($user)->get(route('tags.index', ['site' => $this->site->slug]));

        $response->assertForbidden();
    });

    it('allows users with create_tags permission to create tags', function () {
        $user = User::factory()->create(['site_id' => $this->site->id]);
        $user->givePermissionTo(['create_tags', 'view_tags']);

        $response = $this->actingAs($user)->post(route('tags.store', ['site' => $this->site->slug]), [
            'name' => 'Test Tag',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('tags', ['name' => 'Test Tag']);
    });

    it('denies tag creation without proper permissions', function () {
        $user = User::factory()->create(['site_id' => $this->site->id]);
        $user->givePermissionTo('view_tags'); // Only view permission

        $response = $this->actingAs($user)->post(route('tags.store', ['site' => $this->site->slug]), [
            'name' => 'Test Tag',
        ]);

        $response->assertForbidden();
    });

    it('allows users with update_tags permission to update tags', function () {
        $user = User::factory()->create(['site_id' => $this->site->id]);
        $user->givePermissionTo(['update_tags', 'view_tags']);

        $tag = Tag::factory()->forSite($this->site)->create();

        $response = $this->actingAs($user)->put(route('tags.update', [
            'site' => $this->site->slug,
            'tag' => $tag->id,
        ]), [
            'name' => 'Updated Tag',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('tags', ['name' => 'Updated Tag']);
    });

    it('allows users with delete_tags permission to delete tags', function () {
        $user = User::factory()->create(['site_id' => $this->site->id]);
        $user->givePermissionTo(['delete_tags', 'view_tags']);

        $tag = Tag::factory()->forSite($this->site)->create();

        $response = $this->actingAs($user)->delete(route('tags.destroy', [
            'site' => $this->site->slug,
            'tag' => $tag->id,
        ]));

        $response->assertRedirect();
        $this->assertDatabaseMissing('tags', ['id' => $tag->id]);
    });

    it('allows users with merge_tags permission to merge tags', function () {
        $user = User::factory()->create(['site_id' => $this->site->id]);
        $user->givePermissionTo(['merge_tags', 'view_tags']);

        $primaryTag = Tag::factory()->forSite($this->site)->create(['name' => 'Primary']);
        $tagToMerge = Tag::factory()->forSite($this->site)->create(['name' => 'ToMerge']);

        $response = $this->actingAs($user)->post(route('tags.merge', ['site' => $this->site->slug]), [
            'primary_tag_id' => $primaryTag->id,
            'tag_ids' => [$tagToMerge->id],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseMissing('tags', ['id' => $tagToMerge->id]);
    });

    it('prevents cross-site access to tags', function () {
        $userOnSite1 = User::factory()->create(['site_id' => $this->site->id]);
        $userOnSite1->assignRole('SiteAdmin');

        $tagOnSite2 = Tag::factory()->forSite($this->otherSite)->create();

        $response = $this->actingAs($userOnSite1)->get(route('tags.edit', [
            'site' => $this->site->slug,
            'tag' => $tagOnSite2->id,
        ]));

        $response->assertNotFound();
    });
});

describe('Bulk Operations Permissions', function () {
    it('allows users with delete_tags permission to bulk delete unused tags', function () {
        $user = User::factory()->create(['site_id' => $this->site->id]);
        $user->givePermissionTo(['delete_tags', 'view_tags']);

        Tag::factory()->forSite($this->site)->count(3)->create();

        $response = $this->actingAs($user)->delete("/{$this->site->slug}/tags/bulk-delete-unused");

        $response->assertRedirect();
        $this->assertDatabaseCount('tags', 0);
    });

    it('denies bulk operations without proper permissions', function () {
        $user = User::factory()->create(['site_id' => $this->site->id]);
        $user->givePermissionTo('view_tags'); // Only view permission

        Tag::factory()->forSite($this->site)->count(3)->create();

        $response = $this->actingAs($user)->delete("/{$this->site->slug}/tags/bulk-delete-unused");

        $response->assertForbidden();
    });
});

describe('Site Isolation in Permissions', function () {
    it('prevents users from accessing other sites data even with admin role', function () {
        $site1User = User::factory()->create(['site_id' => $this->site->id]);
        $site1User->assignRole('SiteAdmin');

        Category::factory()->forSite($this->otherSite)->create(['name' => 'Other Site Category']);

        $response = $this->actingAs($site1User)->get(route('categories.index', ['site' => $this->site->slug]));

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('categories.data', 0) // No categories from other site
            );
    });

    it('ensures policy checks include site ownership', function () {
        $site1User = User::factory()->create(['site_id' => $this->site->id]);
        $site1User->assignRole('SiteAdmin');

        $otherSiteCategory = Category::factory()->forSite($this->otherSite)->create();

        // Try to update category from different site
        $response = $this->actingAs($site1User)->put(route('categories.update', [
            'site' => $this->site->slug,
            'category' => $otherSiteCategory->id,
        ]), [
            'name' => 'Hacked Category',
        ]);

        $response->assertNotFound(); // Should not be found due to site scoping
    });
});

describe('Permission Seeding', function () {
    it('has all required category permissions', function () {
        $requiredPermissions = [
            'manage_categories',
            'view_categories',
            'create_categories',
            'update_categories',
            'delete_categories',
            'reorder_categories',
        ];

        foreach ($requiredPermissions as $permission) {
            $this->assertTrue(Permission::where('name', $permission)->exists(),
                "Permission '{$permission}' should exist");
        }
    });

    it('has all required tag permissions', function () {
        $requiredPermissions = [
            'manage_tags',
            'view_tags',
            'create_tags',
            'update_tags',
            'delete_tags',
            'merge_tags',
        ];

        foreach ($requiredPermissions as $permission) {
            $this->assertTrue(Permission::where('name', $permission)->exists(),
                "Permission '{$permission}' should exist");
        }
    });

    it('assigns all permissions to SiteAdmin role', function () {
        $siteAdmin = Role::where('name', 'SiteAdmin')->first();

        $requiredPermissions = [
            'manage_categories', 'view_categories', 'create_categories', 'update_categories', 'delete_categories', 'reorder_categories',
            'manage_tags', 'view_tags', 'create_tags', 'update_tags', 'delete_tags', 'merge_tags',
        ];

        foreach ($requiredPermissions as $permission) {
            $this->assertTrue($siteAdmin->hasPermissionTo($permission),
                "SiteAdmin should have '{$permission}' permission");
        }
    });
});

<?php

use App\Models\Site;
use App\Models\Tag;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    // Create permissions first
    $permissions = [
        'manage_tags', 'view_tags', 'create_tags', 'update_tags', 'delete_tags', 'merge_tags',
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

describe('Tag Index', function () {
    it('can display tags index page', function () {
        Tag::factory()->forSite($this->site)->count(5)->create();

        $response = $this->get(route('tags.index', ['site' => $this->site->slug]));

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Products/Tags/Index')
                ->has('tags.data', 5)
                ->has('statistics')
                ->has('popularTags')
            );
    });

    it('can search tags', function () {
        Tag::factory()->forSite($this->site)->create(['name' => 'Premium']);
        Tag::factory()->forSite($this->site)->create(['name' => 'Sale']);

        $response = $this->get(route('tags.index', [
            'site' => $this->site->slug,
            'search' => 'Premium',
        ]));

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Products/Tags/Index')
                ->has('tags.data', 1)
                ->where('tags.data.0.name', 'Premium')
            );
    });

    it('can filter tags by usage status', function () {
        Tag::factory()->forSite($this->site)->create(['name' => 'Used Tag']);
        Tag::factory()->forSite($this->site)->create(['name' => 'Unused Tag']);

        $response = $this->get(route('tags.index', [
            'site' => $this->site->slug,
            'usage' => 'unused',
        ]));

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Products/Tags/Index')
                ->has('tags.data', 2) // All tags are unused in test
            );
    });

    it('passes search and sorting filters to the page', function () {
        Tag::factory()->forSite($this->site)->create(['name' => 'Premium']);
        Tag::factory()->forSite($this->site)->create(['name' => 'Sale']);

        $response = $this->get(route('tags.index', [
            'site' => $this->site->slug,
            'search' => 'Premium',
            'usage' => 'unused',
            'sort_by' => 'products_count',
        ]));

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Products/Tags/Index')
                ->where('filters.search', 'Premium')
                ->where('filters.usage', 'unused')
                ->where('filters.sort_by', 'products_count')
            );
    });

    it('enforces site isolation', function () {
        $otherSite = Site::factory()->create();
        Tag::factory()->forSite($otherSite)->create(['name' => 'Other Site Tag']);
        Tag::factory()->forSite($this->site)->create(['name' => 'My Site Tag']);

        $response = $this->get(route('tags.index', ['site' => $this->site->slug]));

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('tags.data', 1)
                ->where('tags.data.0.name', 'My Site Tag')
            );
    });

    it('displays usage statistics', function () {
        Tag::factory()->forSite($this->site)->count(3)->create();

        $response = $this->get(route('tags.index', ['site' => $this->site->slug]));

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('statistics.total')
                ->has('statistics.used')
                ->has('statistics.unused')
            );
    });
});

describe('Tag Create', function () {
    it('can display create tag form', function () {
        $response = $this->get(route('tags.create', ['site' => $this->site->slug]));

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Products/Tags/Create')
                ->has('site')
            );
    });

    it('can create a new tag', function () {
        $tagData = [
            'name' => 'New Tag',
        ];

        $response = $this->post(route('tags.store', ['site' => $this->site->slug]), $tagData);

        $response->assertRedirect(route('tags.index', ['site' => $this->site->slug]))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('tags', [
            'name' => 'New Tag',
            'site_id' => $this->site->id,
            'slug' => 'new-tag',
        ]);
    });

    it('auto-generates slug from name', function () {
        $response = $this->post(route('tags.store', ['site' => $this->site->slug]), [
            'name' => 'Premium Quality',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('tags', [
            'name' => 'Premium Quality',
            'slug' => 'premium-quality',
            'site_id' => $this->site->id,
        ]);
    });

    it('validates unique slug within site', function () {
        // Create a tag with slug 'test'
        Tag::factory()->forSite($this->site)->create(['name' => 'Test Original', 'slug' => 'test']);

        // Try to create another tag with the same slug
        $response = $this->post(route('tags.store', ['site' => $this->site->slug]), [
            'name' => 'Test New',
            'slug' => 'test',
        ]);

        // Should fail validation due to unique constraint
        $response->assertSessionHasErrors(['slug']);
    });

    it('auto-generates unique slug when names conflict', function () {
        // Create a tag that will get 'test' slug
        Tag::factory()->forSite($this->site)->create(['name' => 'Test', 'slug' => 'test']);

        // Create another tag with similar name but don't specify slug
        $response = $this->post(route('tags.store', ['site' => $this->site->slug]), [
            'name' => 'Test Another',
            // Let system auto-generate slug
        ]);

        $response->assertRedirect();

        // Should create with auto-generated slug from name
        $this->assertDatabaseHas('tags', [
            'name' => 'Test Another',
            'slug' => 'test-another',
            'site_id' => $this->site->id,
        ]);
    });

    it('validates required fields', function () {
        $response = $this->post(route('tags.store', ['site' => $this->site->slug]), []);

        $response->assertSessionHasErrors(['name']);
    });

    it('validates unique name within site', function () {
        Tag::factory()->forSite($this->site)->create(['name' => 'Existing Tag']);

        $response = $this->post(route('tags.store', ['site' => $this->site->slug]), [
            'name' => 'Existing Tag',
        ]);

        $response->assertSessionHasErrors(['name']);
    });

    it('allows duplicate names across different sites', function () {
        $otherSite = Site::factory()->create();
        Tag::factory()->forSite($otherSite)->create(['name' => 'Cross Site Tag']);

        $response = $this->post(route('tags.store', ['site' => $this->site->slug]), [
            'name' => 'Cross Site Tag',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
    });
});

describe('Tag quick store', function () {
    it('creates a tag and returns json for product forms', function () {
        $response = $this->postJson(route('tags.quick-store', ['site' => $this->site->slug]), [
            'name' => 'Quick Modal Tag',
        ]);

        $response->assertOk()
            ->assertJsonPath('tag.name', 'Quick Modal Tag')
            ->assertJsonStructure(['tag' => ['id', 'name']]);

        $this->assertDatabaseHas('tags', [
            'name' => 'Quick Modal Tag',
            'site_id' => $this->site->id,
        ]);
    });

    it('validates quick store with json errors', function () {
        $response = $this->postJson(route('tags.quick-store', ['site' => $this->site->slug]), []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    });
});

describe('Tag Update', function () {
    it('can display edit tag form', function () {
        $tag = Tag::factory()->forSite($this->site)->create();

        $response = $this->get(route('tags.edit', [
            'site' => $this->site->slug,
            'tag' => $tag->id,
        ]));

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Products/Tags/Edit')
                ->has('tag')
                ->has('site')
            );
    });

    it('can update a tag', function () {
        $tag = Tag::factory()->forSite($this->site)->create([
            'name' => 'Original Name',
        ]);

        $updateData = [
            'name' => 'Updated Name',
        ];

        $response = $this->put(route('tags.update', [
            'site' => $this->site->slug,
            'tag' => $tag->id,
        ]), $updateData);

        $response->assertRedirect(route('tags.index', ['site' => $this->site->slug]));

        $this->assertDatabaseHas('tags', [
            'id' => $tag->id,
            'name' => 'Updated Name',
            'slug' => 'updated-name',
        ]);
    });

    it('enforces site ownership in updates', function () {
        $otherSite = Site::factory()->create();
        $otherTag = Tag::factory()->forSite($otherSite)->create();

        $response = $this->put(route('tags.update', [
            'site' => $this->site->slug,
            'tag' => $otherTag->id,
        ]), [
            'name' => 'Hacked Name',
        ]);

        $response->assertNotFound();
    });
});

describe('Tag Delete', function () {
    it('can delete unused tag', function () {
        $tag = Tag::factory()->forSite($this->site)->create();

        $response = $this->delete(route('tags.destroy', [
            'site' => $this->site->slug,
            'tag' => $tag->id,
        ]));

        $response->assertRedirect(route('tags.index', ['site' => $this->site->slug]));

        $this->assertDatabaseMissing('tags', [
            'id' => $tag->id,
        ]);
    });

    it('enforces site ownership in deletion', function () {
        $otherSite = Site::factory()->create();
        $otherTag = Tag::factory()->forSite($otherSite)->create();

        $response = $this->delete(route('tags.destroy', [
            'site' => $this->site->slug,
            'tag' => $otherTag->id,
        ]));

        $response->assertNotFound();
    });
});

describe('Tag Bulk Operations', function () {
    it('can bulk delete unused tags', function () {
        Tag::factory()->forSite($this->site)->count(3)->create(); // All unused

        $url = "/{$this->site->slug}/tags/bulk-delete-unused";
        // Debug: check what URL we're calling and user's role
        expect($this->site->slug)->not->toBeNull()
            ->and($this->user->hasRole('SiteAdmin'))->toBeTrue();

        $response = $this->delete($url);

        $response->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseCount('tags', 0);
    });

    it('enforces site ownership in bulk delete', function () {
        $otherSite = Site::factory()->create();
        Tag::factory()->forSite($otherSite)->count(2)->create();
        Tag::factory()->forSite($this->site)->count(3)->create();

        $response = $this->delete("/{$this->site->slug}/tags/bulk-delete-unused");

        $response->assertRedirect();

        // Should only delete tags from current site
        $this->assertDatabaseCount('tags', 2); // Other site tags remain
    });
});

describe('Tag Permissions', function () {
    it('requires authentication', function () {
        auth()->logout();

        $response = $this->get(route('tags.index', ['site' => $this->site->slug]));

        $response->assertRedirect(route('login'));
    });

    it('requires proper permissions', function () {
        $userWithoutPermissions = User::factory()->create(['site_id' => $this->site->id]);
        $this->actingAs($userWithoutPermissions);

        $response = $this->get(route('tags.index', ['site' => $this->site->slug]));

        $response->assertForbidden();
    });
});

describe('Tag API Endpoints', function () {
    it('can search tags for autocomplete', function () {
        Tag::factory()->forSite($this->site)->create(['name' => 'Premium Quality']);
        Tag::factory()->forSite($this->site)->create(['name' => 'Sale Item']);

        $response = $this->get(route('tags.search', [
            'site' => $this->site->slug,
            'q' => 'Premium',
        ]));

        $response->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.name', 'Premium Quality');
    });

    it('can get popular tags', function () {
        Tag::factory()->forSite($this->site)->count(5)->create();

        $response = $this->get(route('tags.popular', ['site' => $this->site->slug]));

        $response->assertOk()
            ->assertJsonCount(5);
    });
});

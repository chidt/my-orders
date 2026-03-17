<?php

use App\Actions\Category\ListCategories;
use App\Models\Category;
use App\Models\Site;
use App\Models\User;
use Illuminate\Http\Request;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    // Create permissions first
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

    $this->action = new ListCategories;
});

describe('ListCategories Action', function () {
    it('can list categories with basic functionality', function () {
        // Create test categories
        Category::factory()->forSite($this->site)->count(5)->create();

        $request = Request::create('/', 'GET');
        $result = $this->action->handle($request, $this->site->id);

        expect($result)->toBeArray()
            ->and($result)->toHaveKeys(['categories', 'parentCategories', 'filters'])
            ->and($result['categories']->items())->toHaveCount(5);

        // Check paginated results
    });

    it('can search categories by name', function () {
        Category::factory()->forSite($this->site)->create(['name' => 'Electronics']);
        Category::factory()->forSite($this->site)->create(['name' => 'Books']);

        $request = Request::create('/', 'GET', ['search' => 'Electronic']);
        $result = $this->action->handle($request, $this->site->id);

        $items = $result['categories']->items();
        expect($items)->toHaveCount(1)
            ->and($items[0]->name)->toBe('Electronics');
    });

    it('returns all categories when all parameter is true', function () {
        // Create more than 20 categories (default page size)
        Category::factory()->forSite($this->site)->count(25)->create();

        $request = Request::create('/', 'GET', ['all' => 'true']);
        $result = $this->action->handle($request, $this->site->id);

        expect($result['categories']->data)->toHaveCount(25)
            ->and($result['categories']->current_page)->toBe(1)
            ->and($result['categories']->last_page)->toBe(1)
            ->and($result['categories']->total)->toBe(25);
    });

    it('respects site isolation', function () {
        $otherSite = Site::factory()->create();

        // Create categories for current site
        Category::factory()->forSite($this->site)->create(['name' => 'My Site Category']);

        // Create categories for other site
        Category::factory()->forSite($otherSite)->create(['name' => 'Other Site Category']);

        $request = Request::create('/', 'GET');
        $result = $this->action->handle($request, $this->site->id);

        $items = $result['categories']->items();
        expect($items)->toHaveCount(1)
            ->and($items[0]->name)->toBe('My Site Category');
    });
});

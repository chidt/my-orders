<?php

use App\Models\Attribute;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function createSiteAdminWithAttributePermissions(): User
{
    $user = User::factory()->create();
    $site = Site::factory()->for($user)->create();

    $role = Role::firstOrCreate(['name' => 'SiteAdmin', 'guard_name' => 'web']);

    $permissions = [
        'manage_attributes',
        'view_attributes',
        'create_attributes',
        'edit_attributes',
        'delete_attributes',
    ];

    foreach ($permissions as $permissionName) {
        $permission = Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'web']);
        if (! $role->hasPermissionTo($permission)) {
            $role->givePermissionTo($permission);
        }
    }

    $user->assignRole($role);

    $user->site_id = $site->id;
    $user->save();

    return $user->refresh();
}

it('allows site admin to view attributes index', function (): void {
    $user = createSiteAdminWithAttributePermissions();
    $site = $user->site;

    Attribute::factory()->forSite($site)->count(3)->sequence(
        fn ($sequence) => ['name' => 'Name '.$sequence->index, 'code' => 'code-'.$sequence->index]
    )->create();

    $response = $this->actingAs($user)
        ->get("/{$site->slug}/attributes");

    $response->assertOk();
});

it('allows site admin to create an attribute', function (): void {
    $user = createSiteAdminWithAttributePermissions();
    $site = $user->site;

    $response = $this->actingAs($user)->post("/{$site->slug}/attributes", [
        'name' => 'Kích Thước',
        'code' => 'size',
        'description' => 'Kích thước sản phẩm',
        'order' => 1,
    ]);

    $response->assertRedirect("/{$site->slug}/attributes");

    $this->assertDatabaseHas('attributes', [
        'name' => 'Kích Thước',
        'code' => 'size',
        'site_id' => $site->id,
    ]);
});

it('validates attribute creation input', function (): void {
    $user = createSiteAdminWithAttributePermissions();
    $site = $user->site;

    $response = $this->actingAs($user)->post("/{$site->slug}/attributes", [
        'name' => '',
        'code' => '',
    ]);

    $response->assertSessionHasErrors(['name', 'code']);
});

it('validates code must be kebab-case', function (): void {
    $user = createSiteAdminWithAttributePermissions();
    $site = $user->site;

    $response = $this->actingAs($user)->post("/{$site->slug}/attributes", [
        'name' => 'Test Attribute',
        'code' => 'Invalid Code!',
    ]);

    $response->assertSessionHasErrors(['code']);
});

it('validates unique name within the same site', function (): void {
    $user = createSiteAdminWithAttributePermissions();
    $site = $user->site;

    Attribute::factory()->forSite($site)->create([
        'name' => 'Kích Thước',
        'code' => 'size',
    ]);

    $response = $this->actingAs($user)->post("/{$site->slug}/attributes", [
        'name' => 'Kích Thước',
        'code' => 'size-2',
    ]);

    $response->assertSessionHasErrors(['name']);
});

it('validates unique code within the same site', function (): void {
    $user = createSiteAdminWithAttributePermissions();
    $site = $user->site;

    Attribute::factory()->forSite($site)->create([
        'name' => 'Kích Thước',
        'code' => 'size',
    ]);

    $response = $this->actingAs($user)->post("/{$site->slug}/attributes", [
        'name' => 'Kích Thước 2',
        'code' => 'size',
    ]);

    $response->assertSessionHasErrors(['code']);
});

it('allows site admin to update an attribute', function (): void {
    $user = createSiteAdminWithAttributePermissions();
    $site = $user->site;

    $attribute = Attribute::factory()->forSite($site)->create([
        'name' => 'Kích Thước',
        'code' => 'size',
    ]);

    $response = $this->actingAs($user)->put("/{$site->slug}/attributes/{$attribute->id}", [
        'name' => 'Kích Thước Updated',
        'code' => 'size-updated',
        'description' => 'Updated description',
        'order' => 5,
    ]);

    $response->assertRedirect("/{$site->slug}/attributes");

    $this->assertDatabaseHas('attributes', [
        'id' => $attribute->id,
        'name' => 'Kích Thước Updated',
        'code' => 'size-updated',
    ]);
});

it('allows site admin to delete an attribute without values', function (): void {
    $user = createSiteAdminWithAttributePermissions();
    $site = $user->site;

    $attribute = Attribute::factory()->forSite($site)->create([
        'name' => 'Test Delete',
        'code' => 'test-delete',
    ]);

    $response = $this->actingAs($user)->delete("/{$site->slug}/attributes/{$attribute->id}");

    $response->assertRedirect("/{$site->slug}/attributes");

    $this->assertDatabaseMissing('attributes', [
        'id' => $attribute->id,
    ]);
});

it('prevents access from user of another site', function (): void {
    $user = createSiteAdminWithAttributePermissions();
    $site = $user->site;

    $otherUser = User::factory()->create();
    $otherSite = Site::factory()->for($otherUser)->create();

    $attribute = Attribute::factory()->forSite($otherSite)->create([
        'name' => 'Other Attribute',
        'code' => 'other-attr',
    ]);

    $response = $this->actingAs($user)->get("/{$otherSite->slug}/attributes");

    $response->assertForbidden();
});

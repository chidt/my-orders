<?php

use App\Models\Site;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function createSiteAdminWithPermissions(): User
{
    $user = User::factory()->create();
    $site = Site::factory()->for($user)->create();

    $role = Role::firstOrCreate(['name' => 'SiteAdmin', 'guard_name' => 'web']);

    $permissions = [
        'manage_suppliers',
        'view_suppliers',
        'create_suppliers',
        'edit_suppliers',
        'delete_suppliers',
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

it('allows site admin to view suppliers index', function (): void {
    $user = createSiteAdminWithPermissions();
    $site = $user->site;

    Supplier::factory()->for($site)->count(2)->create();

    $response = $this->actingAs($user)
        ->get("/{$site->slug}/suppliers");

    $response->assertOk();
});

it('allows site admin to create a supplier', function (): void {
    $user = createSiteAdminWithPermissions();
    $site = $user->site;

    $response = $this->actingAs($user)->post("/{$site->slug}/suppliers", [
        'name' => 'Nhà cung cấp A',
        'person_in_charge' => 'Nguyễn Văn A',
        'phone' => '+84901234567',
        'address' => '123 Đường ABC',
        'description' => 'Ghi chú',
    ]);

    $response->assertRedirect("/{$site->slug}/suppliers");

    $this->assertDatabaseHas('suppliers', [
        'name' => 'Nhà cung cấp A',
        'site_id' => $site->id,
    ]);
});

it('validates supplier creation input', function (): void {
    $user = createSiteAdminWithPermissions();
    $site = $user->site;

    $response = $this->actingAs($user)->post("/{$site->slug}/suppliers", [
        'name' => '',
        'phone' => 'invalid-phone',
    ]);

    $response->assertSessionHasErrors(['name', 'phone']);
});

<?php

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

    $this->superAdmin = User::factory()->create();
    $this->superAdmin->assignRole('Admin');

    $this->regularUser = User::factory()->create();
    $this->regularUser->assignRole('SiteAdmin');
});

describe('Role Management Index', function () {
    it('shows roles index page for authorized users', function () {
        actingAs($this->superAdmin)
            ->get(route('admin.roles.index'))
            ->assertOk();
    });

    it('denies access to unauthorized users', function () {
        actingAs($this->regularUser)
            ->get(route('admin.roles.index'))
            ->assertForbidden();
    });

    it('displays roles with pagination', function () {
        actingAs($this->superAdmin)
            ->get(route('admin.roles.index'))
            ->assertOk()
            ->assertInertia(function ($page) {
                $page->has('roles.data')
                    ->has('roles.links');
            });
    });
});

describe('Role Creation', function () {
    it('shows role creation form for authorized users', function () {
        actingAs($this->superAdmin)
            ->get(route('admin.roles.create'))
            ->assertOk()
            ->assertInertia(function ($page) {
                $page->component('Admin/Roles/Create')
                    ->has('permissions');
            });
    });

    it('denies access to unauthorized users', function () {
        actingAs($this->regularUser)
            ->get(route('admin.roles.create'))
            ->assertForbidden();
    });

    it('creates a new role successfully', function () {
        $permissions = Permission::take(3)->get();

        actingAs($this->superAdmin)
            ->post(route('admin.roles.store'), [
                'name' => 'Test Role',
                'permissions' => $permissions->pluck('id')->toArray(),
            ])
            ->assertRedirect(route('admin.roles.index'))
            ->assertSessionHas('message', 'Tạo vai trò thành công.');

        assertDatabaseHas('roles', [
            'name' => 'Test Role',
            'guard_name' => 'web',
        ]);

        $role = Role::where('name', 'Test Role')->first();
        expect($role->permissions)->toHaveCount(3);
    });

    it('validates required fields when creating a role', function () {
        actingAs($this->superAdmin)
            ->post(route('admin.roles.store'), [])
            ->assertSessionHasErrors(['name']);
    });

    it('validates unique role name', function () {
        actingAs($this->superAdmin)
            ->post(route('admin.roles.store'), [
                'name' => 'Admin', // Already exists from seeder
            ])
            ->assertSessionHasErrors(['name']);
    });

    it('validates permission IDs', function () {
        actingAs($this->superAdmin)
            ->post(route('admin.roles.store'), [
                'name' => 'Test Role',
                'permissions' => [999999], // Non-existent permission ID
            ])
            ->assertSessionHasErrors(['permissions.0']);
    });
});

describe('Role Display', function () {
    it('shows role details for authorized users', function () {
        $role = Role::with(['permissions', 'users'])->first();

        actingAs($this->superAdmin)
            ->get(route('admin.roles.show', $role))
            ->assertOk();
    });

    it('denies access to unauthorized users', function () {
        $role = Role::first();

        actingAs($this->regularUser)
            ->get(route('admin.roles.show', $role))
            ->assertForbidden();
    });
});

describe('Role Editing', function () {
    it('shows role edit form for authorized users', function () {
        $role = Role::with('permissions')->first();

        actingAs($this->superAdmin)
            ->get(route('admin.roles.edit', $role))
            ->assertOk()
            ->assertInertia(function ($page) use ($role) {
                $page->component('Admin/Roles/Edit')
                    ->where('role.id', $role->id)
                    ->has('permissions');
            });
    });

    it('denies access to unauthorized users', function () {
        $role = Role::first();

        actingAs($this->regularUser)
            ->get(route('admin.roles.edit', $role))
            ->assertForbidden();
    });
});

describe('Role Updates', function () {
    it('updates a role successfully', function () {
        $role = Role::create([
            'name' => 'Original Role',
            'guard_name' => 'web',
        ]);

        $permissions = Permission::take(2)->get();

        actingAs($this->superAdmin)
            ->put(route('admin.roles.update', $role), [
                'name' => 'Updated Role',
                'permissions' => $permissions->pluck('id')->toArray(),
            ])
            ->assertRedirect(route('admin.roles.index'))
            ->assertSessionHas('message', 'Cập nhật vai trò thành công.');

        assertDatabaseHas('roles', [
            'id' => $role->id,
            'name' => 'Updated Role',
        ]);

        $role->refresh();
        expect($role->permissions)->toHaveCount(2);
    });

    it('validates required fields when updating a role', function () {
        $role = Role::create([
            'name' => 'Test Role',
            'guard_name' => 'web',
        ]);

        actingAs($this->superAdmin)
            ->put(route('admin.roles.update', $role), [
                'name' => '',
            ])
            ->assertSessionHasErrors(['name']);
    });

    it('validates unique role name when updating', function () {
        $existingRole = Role::first();
        $roleToUpdate = Role::create([
            'name' => 'Role To Update',
            'guard_name' => 'web',
        ]);

        actingAs($this->superAdmin)
            ->put(route('admin.roles.update', $roleToUpdate), [
                'name' => $existingRole->name,
            ])
            ->assertSessionHasErrors(['name']);
    });

    it('allows updating role with same name', function () {
        $role = Role::create([
            'name' => 'Test Role',
            'guard_name' => 'web',
        ]);

        actingAs($this->superAdmin)
            ->put(route('admin.roles.update', $role), [
                'name' => 'Test Role', // Same name should be allowed
            ])
            ->assertRedirect(route('admin.roles.index'));
    });

    it('clears permissions when none are provided', function () {
        $role = Role::create([
            'name' => 'Test Role',
            'guard_name' => 'web',
        ]);
        $permissions = Permission::take(3)->get();
        $role->givePermissionTo($permissions);

        actingAs($this->superAdmin)
            ->put(route('admin.roles.update', $role), [
                'name' => 'Updated Role',
                'permissions' => [], // Clear all permissions
            ])
            ->assertRedirect(route('admin.roles.index'));

        $role->refresh();
        expect($role->permissions)->toHaveCount(0);
    });
});

describe('Role Deletion', function () {
    it('deletes a role successfully when no users assigned', function () {
        $role = Role::create([
            'name' => 'Deletable Role',
            'guard_name' => 'web',
        ]);

        actingAs($this->superAdmin)
            ->delete(route('admin.roles.destroy', $role))
            ->assertRedirect(route('admin.roles.index'))
            ->assertSessionHas('message', 'Xoá vai trò thành công.');

        assertDatabaseMissing('roles', [
            'id' => $role->id,
        ]);
    });

    it('prevents deletion of role with assigned users', function () {
        $role = Role::create([
            'name' => 'Role With Users',
            'guard_name' => 'web',
        ]);
        $user = User::factory()->create();
        $user->assignRole($role);

        actingAs($this->superAdmin)
            ->delete(route('admin.roles.destroy', $role))
            ->assertRedirect(route('admin.roles.index'))
            ->assertSessionHas('error', 'Không thể xoá vai trò đang sử dụng cho người dùng.');

        assertDatabaseHas('roles', [
            'id' => $role->id,
        ]);
    });

    it('denies deletion to unauthorized users', function () {
        $role = Role::create([
            'name' => 'Test Role',
            'guard_name' => 'web',
        ]);

        actingAs($this->regularUser)
            ->delete(route('admin.roles.destroy', $role))
            ->assertForbidden();
    });
});

describe('Permission Management', function () {
    it('syncs permissions correctly when creating role', function () {
        $permissions = Permission::take(5)->get();

        actingAs($this->superAdmin)
            ->post(route('admin.roles.store'), [
                'name' => 'Permission Test Role',
                'permissions' => $permissions->pluck('id')->toArray(),
            ]);

        $role = Role::where('name', 'Permission Test Role')->first();

        expect($role->permissions)->toHaveCount(5);

        foreach ($permissions as $permission) {
            expect($role->hasPermissionTo($permission))->toBeTrue();
        }
    });

    it('syncs permissions correctly when updating role', function () {
        $role = Role::create([
            'name' => 'Test Role',
            'guard_name' => 'web',
        ]);
        $initialPermissions = Permission::take(3)->get();
        $role->givePermissionTo($initialPermissions);

        $newPermissions = Permission::skip(3)->take(4)->get();

        actingAs($this->superAdmin)
            ->put(route('admin.roles.update', $role), [
                'name' => $role->name,
                'permissions' => $newPermissions->pluck('id')->toArray(),
            ]);

        $role->refresh();
        expect($role->permissions)->toHaveCount(4);

        foreach ($newPermissions as $permission) {
            expect($role->hasPermissionTo($permission))->toBeTrue();
        }

        foreach ($initialPermissions as $permission) {
            expect($role->hasPermissionTo($permission))->toBeFalse();
        }
    });
});

describe('Authorization Tests', function () {
    it('requires manage roles permission for create, edit, delete actions', function () {
        $userWithoutPermission = User::factory()->create();
        $role = Role::create([
            'name' => 'Test Role',
            'guard_name' => 'web',
        ]);

        // Test create permission
        actingAs($userWithoutPermission)
            ->get(route('admin.roles.create'))
            ->assertForbidden();

        actingAs($userWithoutPermission)
            ->post(route('admin.roles.store'), ['name' => 'Test'])
            ->assertForbidden();

        // Test edit permission
        actingAs($userWithoutPermission)
            ->get(route('admin.roles.edit', $role))
            ->assertForbidden();

        actingAs($userWithoutPermission)
            ->put(route('admin.roles.update', $role), ['name' => 'Test'])
            ->assertForbidden();

        // Test delete permission
        actingAs($userWithoutPermission)
            ->delete(route('admin.roles.destroy', $role))
            ->assertForbidden();
    });

    it('allows view operations with view roles permission', function () {
        $user = User::factory()->create();
        $viewRolesPermission = Permission::firstOrCreate(['name' => 'view_roles']);
        $user->givePermissionTo($viewRolesPermission);

        $role = Role::create([
            'name' => 'Test Role',
            'guard_name' => 'web',
        ]);

        actingAs($user)
            ->get(route('admin.roles.index'))
            ->assertOk();

        actingAs($user)
            ->get(route('admin.roles.show', $role))
            ->assertOk();
    });
});

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

describe('Permission Management Index', function () {
    it('shows permissions index page for authorized users', function () {
        actingAs($this->superAdmin)
            ->get(route('admin.permissions.index'))
            ->assertOk();
    });

    it('denies access to unauthorized users', function () {
        actingAs($this->regularUser)
            ->get(route('admin.permissions.index'))
            ->assertForbidden();
    });

    it('displays permissions with pagination', function () {
        actingAs($this->superAdmin)
            ->get(route('admin.permissions.index'))
            ->assertOk()
            ->assertInertia(function ($page) {
                $page->has('permissions.data')
                    ->has('permissions.links');
            });
    });
});

describe('Permission Creation', function () {
    it('shows permission creation form for authorized users', function () {
        actingAs($this->superAdmin)
            ->get(route('admin.permissions.create'))
            ->assertOk()
            ->assertInertia(function ($page) {
                $page->component('Admin/Permissions/Create');
            });
    });

    it('denies access to unauthorized users', function () {
        actingAs($this->regularUser)
            ->get(route('admin.permissions.create'))
            ->assertForbidden();
    });

    it('creates a new permission successfully', function () {
        actingAs($this->superAdmin)
            ->post(route('admin.permissions.store'), [
                'name' => 'test_permission',
            ])
            ->assertRedirect(route('admin.permissions.index'))
            ->assertSessionHas('message', 'Tạo quyền hạn thành công.');

        assertDatabaseHas('permissions', [
            'name' => 'test_permission',
            'guard_name' => 'web',
        ]);
    });

    it('validates required fields', function () {
        actingAs($this->superAdmin)
            ->post(route('admin.permissions.store'), [])
            ->assertSessionHasErrors(['name']);
    });

    it('validates unique permission name', function () {
        $permission = Permission::first();

        actingAs($this->superAdmin)
            ->post(route('admin.permissions.store'), [
                'name' => $permission->name,
            ])
            ->assertSessionHasErrors(['name']);
    });

    it('denies creation to unauthorized users', function () {
        actingAs($this->regularUser)
            ->post(route('admin.permissions.store'), [
                'name' => 'test_permission',
            ])
            ->assertForbidden();
    });
});

describe('Permission Display', function () {
    it('shows permission details for authorized users', function () {
        $permission = Permission::first();

        actingAs($this->superAdmin)
            ->get(route('admin.permissions.show', $permission))
            ->assertOk();
    });

    it('denies access to unauthorized users', function () {
        $permission = Permission::first();

        actingAs($this->regularUser)
            ->get(route('admin.permissions.show', $permission))
            ->assertForbidden();
    });
});

describe('Permission Editing', function () {
    it('shows permission edit form for authorized users', function () {
        $permission = Permission::first();

        actingAs($this->superAdmin)
            ->get(route('admin.permissions.edit', $permission))
            ->assertOk()
            ->assertInertia(function ($page) use ($permission) {
                $page->component('Admin/Permissions/Edit')
                    ->where('permission.id', $permission->id);
            });
    });

    it('denies access to unauthorized users', function () {
        $permission = Permission::first();

        actingAs($this->regularUser)
            ->get(route('admin.permissions.edit', $permission))
            ->assertForbidden();
    });
});

describe('Permission Updates', function () {
    it('updates a permission successfully', function () {
        $permission = Permission::create([
            'name' => 'original_permission',
            'guard_name' => 'web',
        ]);

        actingAs($this->superAdmin)
            ->put(route('admin.permissions.update', $permission), [
                'name' => 'updated_permission',
            ])
            ->assertRedirect(route('admin.permissions.index'))
            ->assertSessionHas('message', 'Cập nhật quyền hạn thành công.');

        assertDatabaseHas('permissions', [
            'id' => $permission->id,
            'name' => 'updated_permission',
        ]);
    });

    it('validates unique permission name on update', function () {
        $permission1 = Permission::create([
            'name' => 'permission_one',
            'guard_name' => 'web',
        ]);
        $permission2 = Permission::create([
            'name' => 'permission_two',
            'guard_name' => 'web',
        ]);

        actingAs($this->superAdmin)
            ->put(route('admin.permissions.update', $permission1), [
                'name' => $permission2->name,
            ])
            ->assertSessionHasErrors(['name']);
    });

    it('allows updating permission with same name', function () {
        $permission = Permission::create([
            'name' => 'test_permission',
            'guard_name' => 'web',
        ]);

        actingAs($this->superAdmin)
            ->put(route('admin.permissions.update', $permission), [
                'name' => 'test_permission', // Same name
            ])
            ->assertRedirect(route('admin.permissions.index'))
            ->assertSessionHas('message');
    });

    it('denies updates to unauthorized users', function () {
        $permission = Permission::create([
            'name' => 'test_permission',
            'guard_name' => 'web',
        ]);

        actingAs($this->regularUser)
            ->put(route('admin.permissions.update', $permission), [
                'name' => 'updated_permission',
            ])
            ->assertForbidden();
    });
});

describe('Permission Deletion', function () {
    it('deletes a permission successfully when no roles assigned', function () {
        $permission = Permission::create([
            'name' => 'deletable_permission',
            'guard_name' => 'web',
        ]);

        actingAs($this->superAdmin)
            ->delete(route('admin.permissions.destroy', $permission))
            ->assertRedirect(route('admin.permissions.index'))
            ->assertSessionHas('message', 'Xoá quyền hạn thành công.');

        assertDatabaseMissing('permissions', [
            'id' => $permission->id,
        ]);
    });

    it('prevents deletion of permission with assigned roles', function () {
        $permission = Permission::create([
            'name' => 'permission_with_roles',
            'guard_name' => 'web',
        ]);
        $role = Role::create([
            'name' => 'Test Role',
            'guard_name' => 'web',
        ]);
        $role->givePermissionTo($permission);

        actingAs($this->superAdmin)
            ->delete(route('admin.permissions.destroy', $permission))
            ->assertRedirect(route('admin.permissions.index'))
            ->assertSessionHas('error', 'Không thể xoá quyền hạn đang sử dụng cho vai trò.');

        assertDatabaseHas('permissions', [
            'id' => $permission->id,
        ]);
    });

    it('denies deletion to unauthorized users', function () {
        $permission = Permission::create([
            'name' => 'test_permission',
            'guard_name' => 'web',
        ]);

        actingAs($this->regularUser)
            ->delete(route('admin.permissions.destroy', $permission))
            ->assertForbidden();
    });
});

describe('Authorization Tests', function () {
    it('requires manage permissions permission for create, edit, delete actions', function () {
        $userWithoutPermission = User::factory()->create();
        $permission = Permission::create([
            'name' => 'test_permission',
            'guard_name' => 'web',
        ]);

        // Test create permission
        actingAs($userWithoutPermission)
            ->get(route('admin.permissions.create'))
            ->assertForbidden();

        actingAs($userWithoutPermission)
            ->post(route('admin.permissions.store'), ['name' => 'test'])
            ->assertForbidden();

        // Test edit permission
        actingAs($userWithoutPermission)
            ->get(route('admin.permissions.edit', $permission))
            ->assertForbidden();

        actingAs($userWithoutPermission)
            ->put(route('admin.permissions.update', $permission), ['name' => 'test'])
            ->assertForbidden();

        // Test delete permission
        actingAs($userWithoutPermission)
            ->delete(route('admin.permissions.destroy', $permission))
            ->assertForbidden();
    });

    it('allows view operations with view permissions permission', function () {
        $user = User::factory()->create();
        $viewPermissionsPermission = Permission::firstOrCreate(['name' => 'view_permissions']);
        $user->givePermissionTo($viewPermissionsPermission);

        $permission = Permission::create([
            'name' => 'test_permission',
            'guard_name' => 'web',
        ]);

        actingAs($user)
            ->get(route('admin.permissions.index'))
            ->assertOk();

        actingAs($user)
            ->get(route('admin.permissions.show', $permission))
            ->assertOk();
    });
});

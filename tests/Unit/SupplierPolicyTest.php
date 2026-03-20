<?php

use App\Models\Site;
use App\Models\Supplier;
use App\Models\User;
use App\Policies\SupplierPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    $this->policy = new SupplierPolicy;
});

it('allows site owner with permission to view suppliers', function (): void {
    $siteOwner = User::factory()->create();
    $site = Site::factory()->for($siteOwner)->create();
    $supplier = Supplier::factory()->for($site)->create();

    $permission = Permission::firstOrCreate(['name' => 'view_suppliers', 'guard_name' => 'web']);
    $role = Role::firstOrCreate(['name' => 'TestRole']);
    $role->givePermissionTo($permission);
    $siteOwner->assignRole($role);

    expect($this->policy->view($siteOwner, $supplier))->toBeTrue();
});

it('denies non owner even with permission', function (): void {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();

    $site = Site::factory()->for($owner)->create();
    $supplier = Supplier::factory()->for($site)->create();

    $permission = Permission::firstOrCreate(['name' => 'view_suppliers', 'guard_name' => 'web']);
    $role = Role::firstOrCreate(['name' => 'AnotherRole']);
    $role->givePermissionTo($permission);
    $otherUser->assignRole($role);

    expect($this->policy->view($otherUser, $supplier))->toBeFalse();
});

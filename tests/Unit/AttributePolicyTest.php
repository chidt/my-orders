<?php

use App\Models\Attribute;
use App\Models\Site;
use App\Models\User;
use App\Policies\AttributePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    $this->policy = new AttributePolicy;
});

it('allows site owner with permission to view attributes', function (): void {
    $siteOwner = User::factory()->create();
    $site = Site::factory()->for($siteOwner)->create();
    $attribute = Attribute::factory()->forSite($site)->create([
        'name' => 'Test Attr',
        'code' => 'test-attr',
    ]);

    $permission = Permission::firstOrCreate(['name' => 'view_attributes', 'guard_name' => 'web']);
    $role = Role::firstOrCreate(['name' => 'TestRole']);
    $role->givePermissionTo($permission);
    $siteOwner->assignRole($role);

    expect($this->policy->view($siteOwner, $attribute))->toBeTrue();
});

it('denies non owner even with permission', function (): void {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();

    $site = Site::factory()->for($owner)->create();
    $attribute = Attribute::factory()->forSite($site)->create([
        'name' => 'Test Attr',
        'code' => 'test-attr',
    ]);

    $permission = Permission::firstOrCreate(['name' => 'view_attributes', 'guard_name' => 'web']);
    $role = Role::firstOrCreate(['name' => 'AnotherRole']);
    $role->givePermissionTo($permission);
    $otherUser->assignRole($role);

    expect($this->policy->view($otherUser, $attribute))->toBeFalse();
});

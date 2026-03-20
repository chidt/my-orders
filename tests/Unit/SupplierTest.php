<?php

use App\Models\Site;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('belongs to a site', function (): void {
    $site = Site::factory()->create();
    $supplier = Supplier::factory()->for($site)->create();

    expect($supplier->site)->not->toBeNull();
    expect($supplier->site->id)->toBe($site->id);
});

it('scopes suppliers by site', function (): void {
    $siteA = Site::factory()->create();
    $siteB = Site::factory()->create();

    $supplierA = Supplier::factory()->for($siteA)->create();
    Supplier::factory()->for($siteB)->create();

    $results = Supplier::forSite($siteA->id)->get();

    expect($results)->toHaveCount(1);
    expect($results->first()?->id)->toBe($supplierA->id);
});

it('can determine if it can be deleted based on products', function (): void {
    $site = Site::factory()->create();
    $supplier = Supplier::factory()->for($site)->create();

    expect($supplier->canBeDeleted())->toBeTrue();
});

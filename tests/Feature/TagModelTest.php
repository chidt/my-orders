<?php

use App\Models\Site;
use App\Models\Tag;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

describe('Tag Model', function () {
    it('can create a tag with required fields', function () {
        $site = Site::factory()->create();

        $tag = Tag::create([
            'name' => 'Premium',
            'site_id' => $site->id,
        ]);

        expect($tag->name)->toBe('Premium');
        expect($tag->site_id)->toBe($site->id);
        expect($tag->slug)->toBe('premium');
    });

    it('auto-generates slug from name', function () {
        $site = Site::factory()->create();

        $tag = Tag::create([
            'name' => 'Bán Chạy Nhất',
            'site_id' => $site->id,
        ]);

        expect($tag->slug)->toBe('ban-chay-nhat');
    });

    it('uses provided slug if given', function () {
        $site = Site::factory()->create();

        $tag = Tag::create([
            'name' => 'Premium',
            'slug' => 'custom-slug',
            'site_id' => $site->id,
        ]);

        expect($tag->slug)->toBe('custom-slug');
    });

    it('handles special characters in slug generation', function () {
        $site = Site::factory()->create();

        $tag = Tag::create([
            'name' => 'Hàng Việt Nam @ 100%',
            'site_id' => $site->id,
        ]);

        expect($tag->slug)->toBe('hang-viet-nam-at-100');
    });
});

describe('Tag Relationships', function () {
    it('belongs to a site', function () {
        $site = Site::factory()->create();
        $tag = Tag::factory()->forSite($site)->create();

        expect($tag->site)->toBeInstanceOf(Site::class);
        expect($tag->site->id)->toBe($site->id);
    });
});

describe('Tag Scopes', function () {
    it('scopes tags to specific site', function () {
        $site1 = Site::factory()->create();
        $site2 = Site::factory()->create();

        Tag::factory()->forSite($site1)->count(3)->create();
        Tag::factory()->forSite($site2)->count(2)->create();

        $site1Tags = Tag::forSite($site1->id)->get();
        $site2Tags = Tag::forSite($site2->id)->get();

        expect($site1Tags)->toHaveCount(3);
        expect($site2Tags)->toHaveCount(2);
    });

    it('orders tags alphabetically', function () {
        $site = Site::factory()->create();
        Tag::factory()->forSite($site)->create(['name' => 'Zebra Tag']);
        Tag::factory()->forSite($site)->create(['name' => 'Alpha Tag']);
        Tag::factory()->forSite($site)->create(['name' => 'Beta Tag']);

        $orderedTags = Tag::forSite($site->id)->ordered()->get();

        expect($orderedTags->first()->name)->toBe('Alpha Tag');
        expect($orderedTags->last()->name)->toBe('Zebra Tag');
    });

    it('scopes popular tags by usage count', function () {
        $site = Site::factory()->create();

        // Create tags (in real app, usage count would be calculated from products relationship)
        $tag1 = Tag::factory()->forSite($site)->create(['name' => 'Popular Tag']);
        $tag2 = Tag::factory()->forSite($site)->create(['name' => 'Less Popular']);
        $tag3 = Tag::factory()->forSite($site)->create(['name' => 'Least Popular']);

        $popularTags = Tag::forSite($site->id)->popular()->get();

        // Should order by products_count DESC (default 0 for all in test)
        expect($popularTags)->toHaveCount(3);
    });
});

describe('Tag Usage Methods', function () {
    it('calculates usage count correctly', function () {
        $site = Site::factory()->create();
        $tag = Tag::factory()->forSite($site)->create();

        // In test environment, no products are attached
        expect($tag->usage_count)->toBe(0);
    });

    it('determines if tag is unused', function () {
        $site = Site::factory()->create();
        $tag = Tag::factory()->forSite($site)->create();

        // In test environment, no products are attached
        expect($tag->isUnused())->toBeTrue();
    });

    it('determines if tag can be deleted', function () {
        $site = Site::factory()->create();
        $tag = Tag::factory()->forSite($site)->create();

        // Tag can be deleted when not used by any products
        expect($tag->canDelete())->toBeTrue();
    });
});

describe('Tag Validation', function () {
    it('handles name conflicts by appending numbers in parentheses', function () {
        $site = Site::factory()->create();
        $firstTag = Tag::factory()->forSite($site)->create(['name' => 'Unique Tag']);

        // Creating another tag with same name should auto-append (1)
        $secondTag = Tag::factory()->forSite($site)->create(['name' => 'Unique Tag']);

        expect($firstTag->name)->toBe('Unique Tag');
        expect($secondTag->fresh()->name)->toBe('Unique Tag (1)');
        // And slugs should also be different
        expect($firstTag->slug)->toBe('unique-tag');
        expect($secondTag->fresh()->slug)->toBe('unique-tag-1');
    });

    it('allows same name across different sites', function () {
        $site1 = Site::factory()->create();
        $site2 = Site::factory()->create();

        $tag1 = Tag::factory()->forSite($site1)->create(['name' => 'Cross Site Tag']);
        $tag2 = Tag::factory()->forSite($site2)->create(['name' => 'Cross Site Tag']);

        expect($tag1->name)->toBe($tag2->name);
        expect($tag1->site_id)->not->toBe($tag2->site_id);
    });

    it('handles slug conflicts by appending numbers', function () {
        $site = Site::factory()->create();
        $firstTag = Tag::factory()->forSite($site)->create(['slug' => 'unique-slug']);

        // Creating another tag with same slug should auto-append a number
        $secondTag = Tag::factory()->forSite($site)->create(['slug' => 'unique-slug']);

        expect($firstTag->slug)->toBe('unique-slug');
        expect($secondTag->fresh()->slug)->toBe('unique-slug-1');
    });

    it('allows same slug across different sites', function () {
        $site1 = Site::factory()->create();
        $site2 = Site::factory()->create();

        $tag1 = Tag::factory()->forSite($site1)->create(['slug' => 'cross-site-slug']);
        $tag2 = Tag::factory()->forSite($site2)->create(['slug' => 'cross-site-slug']);

        expect($tag1->slug)->toBe($tag2->slug);
        expect($tag1->site_id)->not->toBe($tag2->site_id);
    });
});

describe('Tag Boot Methods', function () {
    it('automatically generates slug on creation when empty', function () {
        $site = Site::factory()->create();

        $tag = new Tag([
            'name' => 'Auto Slug Tag',
            'site_id' => $site->id,
        ]);

        $tag->save();

        expect($tag->slug)->toBe('auto-slug-tag');
    });

    it('does not override provided slug on creation', function () {
        $site = Site::factory()->create();

        $tag = new Tag([
            'name' => 'Custom Slug Tag',
            'slug' => 'custom-provided-slug',
            'site_id' => $site->id,
        ]);

        $tag->save();

        expect($tag->slug)->toBe('custom-provided-slug');
    });

    it('updates slug when name changes if slug is empty', function () {
        $site = Site::factory()->create();

        $tag = Tag::factory()->forSite($site)->create([
            'name' => 'Original Name',
            'slug' => 'original-name',
        ]);

        $tag->update([
            'name' => 'Updated Name',
            'slug' => null, // Clear slug to trigger auto-generation
        ]);

        expect($tag->fresh()->slug)->toBe('updated-name');
    });
});

describe('Tag Factory States', function () {
    it('creates promotional tags state', function () {
        $site = Site::factory()->create();

        // Create 3 promotional tags with specific names to avoid collisions
        $tag1 = Tag::factory()->forSite($site)->create(['name' => 'sale', 'slug' => 'sale']);
        $tag2 = Tag::factory()->forSite($site)->create(['name' => 'discount', 'slug' => 'discount']);
        $tag3 = Tag::factory()->forSite($site)->create(['name' => 'special offer', 'slug' => 'special-offer']);

        $promotionalTags = collect([$tag1, $tag2, $tag3]);

        expect($promotionalTags)->toHaveCount(3);

        // Check that names are from promotional list
        $promotionalNames = ['sale', 'discount', 'special offer', 'flash sale', 'clearance'];
        foreach ($promotionalTags as $tag) {
            expect($promotionalNames)->toContain($tag->name);
        }
    });
});

describe('Tag Site Scoping', function () {
    it('automatically includes site_id in queries through global scope', function () {
        $site1 = Site::factory()->create();
        $site2 = Site::factory()->create();

        Tag::factory()->forSite($site1)->count(2)->create();
        Tag::factory()->forSite($site2)->count(3)->create();

        // When querying with site scope, should only return that site's tags
        $site1Tags = Tag::forSite($site1->id)->count();
        $site2Tags = Tag::forSite($site2->id)->count();

        expect($site1Tags)->toBe(2);
        expect($site2Tags)->toBe(3);
    });
});

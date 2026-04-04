<?php

namespace App\Actions\Tag;

use App\Models\Tag;

class ListTags
{
    public function execute(int $siteId, array $filters = []): array
    {
        $search = (string) ($filters['search'] ?? '');
        $usage = (string) ($filters['usage'] ?? '');
        $sortBy = (string) ($filters['sort_by'] ?? 'name');
        $sortDirection = ((string) ($filters['sort_direction'] ?? 'asc')) === 'desc' ? 'desc' : 'asc';

        $query = Tag::forSite($siteId)->withCount('products');

        if ($search !== '') {
            $query->where('name', 'like', '%'.$search.'%');
        }

        if ($usage === 'used') {
            $query->has('products');
        } elseif ($usage === 'unused') {
            $query->doesntHave('products');
        }

        if (in_array($sortBy, ['name', 'products_count', 'created_at'], true)) {
            $query->orderBy($sortBy, $sortDirection);
        } else {
            $query->ordered();
        }

        $tags = $query->paginate(50)->withQueryString();

        $totalTags = Tag::forSite($siteId)->count();
        $usedTags = Tag::forSite($siteId)->has('products')->count();

        return [
            'tags' => $tags,
            'statistics' => [
                'total' => $totalTags,
                'used' => $usedTags,
                'unused' => $totalTags - $usedTags,
            ],
            'popularTags' => Tag::forSite($siteId)->popular()->limit(10)->get(),
        ];
    }
}

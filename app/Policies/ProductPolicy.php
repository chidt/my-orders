<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_products') || $user->can('manage_products');
    }

    public function view(User $user, Product $product): bool
    {
        return ($user->can('view_products') || $user->can('manage_products'))
            && $this->belongsToUserSite($user, $product);
    }

    public function create(User $user): bool
    {
        return $user->can('manage_products');
    }

    public function update(User $user, Product $product): bool
    {
        return $user->can('manage_products')
            && $this->belongsToUserSite($user, $product);
    }

    public function delete(User $user, Product $product): bool
    {
        return $user->can('manage_products')
            && $this->belongsToUserSite($user, $product);
    }

    private function belongsToUserSite(User $user, Product $product): bool
    {
        return $user->site_id === $product->site_id;
    }
}

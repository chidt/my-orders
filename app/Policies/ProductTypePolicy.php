<?php

namespace App\Policies;

use App\Models\ProductType;
use App\Models\User;

class ProductTypePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_product_types') || $user->can('manage_product_types');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ProductType $productType): bool
    {
        return ($user->can('view_product_types') || $user->can('manage_product_types'))
            && $this->belongsToUserSite($user, $productType);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('manage_product_types');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ProductType $productType): bool
    {
        return $user->can('manage_product_types')
            && $this->belongsToUserSite($user, $productType);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ProductType $productType): bool
    {
        return $user->can('manage_product_types')
            && $this->belongsToUserSite($user, $productType)
            && $productType->canDelete();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ProductType $productType): bool
    {
        return $user->can('manage_product_types')
            && $this->belongsToUserSite($user, $productType);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ProductType $productType): bool
    {
        return $user->can('manage_product_types')
            && $this->belongsToUserSite($user, $productType);
    }

    /**
     * Check if the product type belongs to the user's current site.
     */
    private function belongsToUserSite(User $user, ProductType $productType): bool
    {
        return $user->site_id === $productType->site_id;
    }
}

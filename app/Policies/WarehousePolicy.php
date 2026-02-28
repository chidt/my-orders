<?php

namespace App\Policies;

use App\Models\Site;
use App\Models\User;
use App\Models\Warehouse;

class WarehousePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user, ?Site $site = null): bool
    {
        // User must have manage_warehouses permission
        if (! $user->can('manage_warehouses')) {
            return false;
        }

        // If site is provided, check ownership
        if ($site) {
            return $this->userOwnsSite($user, $site);
        }

        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Warehouse $warehouse): bool
    {
        return $user->can('view_warehouses') && $this->userOwnsWarehouse($user, $warehouse);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, ?Site $site = null): bool
    {
        // User must have create_warehouses permission
        if (! $user->can('create_warehouses')) {
            return false;
        }

        // If site is provided, check ownership
        if ($site) {
            return $this->userOwnsSite($user, $site);
        }

        // Check if user has a site (for general creation)
        return $user->site_id !== null;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Warehouse $warehouse): bool
    {
        return $user->can('edit_warehouses') && $this->userOwnsWarehouse($user, $warehouse);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Warehouse $warehouse): bool
    {
        // Only check permissions and ownership - business rules should be handled in controller/action
        return $user->can('delete_warehouses') && $this->userOwnsWarehouse($user, $warehouse);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Warehouse $warehouse): bool
    {
        return $user->can('edit_warehouses') && $this->userOwnsWarehouse($user, $warehouse);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Warehouse $warehouse): bool
    {
        return $user->can('delete_warehouses') && $this->userOwnsWarehouse($user, $warehouse);
    }

    /**
     * Check if user owns the warehouse through site ownership.
     */
    private function userOwnsWarehouse(User $user, Warehouse $warehouse): bool
    {
        if (! $warehouse->site) {
            return false;
        }

        return $this->userOwnsSite($user, $warehouse->site);
    }

    /**
     * Check if user owns the site.
     */
    private function userOwnsSite(User $user, Site $site): bool
    {
        return $site->user_id === $user->id;
    }
}

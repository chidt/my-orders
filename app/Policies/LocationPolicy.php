<?php

namespace App\Policies;

use App\Models\Location;
use App\Models\User;
use App\Models\Warehouse;

class LocationPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user, Warehouse $warehouse): bool
    {
        return $user->can('view_warehouse_locations') && $this->userOwnsWarehouse($user, $warehouse);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Location $location): bool
    {
        return $user->can('view_warehouse_locations') && $this->userOwnsLocation($user, $location);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, Warehouse $warehouse): bool
    {
        return $user->can('create_warehouse_locations') && $this->userOwnsWarehouse($user, $warehouse);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Location $location): bool
    {
        return $user->can('edit_warehouse_locations') && $this->userOwnsLocation($user, $location);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Location $location): bool
    {
        // Only check permissions and ownership - business rules should be handled in controller/action
        return $user->can('delete_warehouse_locations') && $this->userOwnsLocation($user, $location);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Location $location): bool
    {
        return $user->can('edit_warehouse_locations') && $this->userOwnsLocation($user, $location);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Location $location): bool
    {
        return $user->can('delete_warehouse_locations') && $this->userOwnsLocation($user, $location);
    }

    /**
     * Check if user owns the location through warehouse and site ownership.
     */
    private function userOwnsLocation(User $user, Location $location): bool
    {
        if (! $location->warehouse) {
            return false;
        }

        return $this->userOwnsWarehouse($user, $location->warehouse);
    }

    /**
     * Check if user owns the warehouse through site ownership.
     */
    private function userOwnsWarehouse(User $user, Warehouse $warehouse): bool
    {
        if (! $warehouse->site) {
            return false;
        }

        return $user->sites()->where('sites.id', $warehouse->site->id)->exists();
    }
}

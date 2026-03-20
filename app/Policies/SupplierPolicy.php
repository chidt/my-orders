<?php

namespace App\Policies;

use App\Models\Site;
use App\Models\Supplier;
use App\Models\User;

class SupplierPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user, ?Site $site = null): bool
    {
        if (! $user->can('manage_suppliers')) {
            return false;
        }

        if ($site) {
            return $this->userOwnsSite($user, $site);
        }

        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Supplier $supplier): bool
    {
        return $user->can('view_suppliers') && $this->userOwnsSupplier($user, $supplier);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, ?Site $site = null): bool
    {
        if (! $user->can('create_suppliers')) {
            return false;
        }

        if ($site) {
            return $this->userOwnsSite($user, $site);
        }

        return $user->site_id !== null;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Supplier $supplier): bool
    {
        return $user->can('edit_suppliers') && $this->userOwnsSupplier($user, $supplier);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Supplier $supplier): bool
    {
        return $user->can('delete_suppliers') && $this->userOwnsSupplier($user, $supplier);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Supplier $supplier): bool
    {
        return $user->can('edit_suppliers') && $this->userOwnsSupplier($user, $supplier);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Supplier $supplier): bool
    {
        return $user->can('delete_suppliers') && $this->userOwnsSupplier($user, $supplier);
    }

    /**
     * Check if user owns the supplier through site ownership.
     */
    private function userOwnsSupplier(User $user, Supplier $supplier): bool
    {
        if (! $supplier->site) {
            return false;
        }

        return $this->userOwnsSite($user, $supplier->site);
    }

    /**
     * Check if user owns the site.
     */
    private function userOwnsSite(User $user, Site $site): bool
    {
        return $site->user_id === $user->id;
    }
}

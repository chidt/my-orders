<?php

namespace App\Policies;

use App\Models\Attribute;
use App\Models\Site;
use App\Models\User;

class AttributePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user, ?Site $site = null): bool
    {
        if (! $user->can('manage_attributes')) {
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
    public function view(User $user, Attribute $attribute): bool
    {
        return $user->can('view_attributes') && $this->userOwnsAttribute($user, $attribute);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, ?Site $site = null): bool
    {
        if (! $user->can('create_attributes')) {
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
    public function update(User $user, Attribute $attribute): bool
    {
        return $user->can('edit_attributes') && $this->userOwnsAttribute($user, $attribute);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Attribute $attribute): bool
    {
        return $user->can('delete_attributes') && $this->userOwnsAttribute($user, $attribute);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Attribute $attribute): bool
    {
        return $user->can('edit_attributes') && $this->userOwnsAttribute($user, $attribute);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Attribute $attribute): bool
    {
        return $user->can('delete_attributes') && $this->userOwnsAttribute($user, $attribute);
    }

    /**
     * Check if user owns the attribute through site ownership.
     */
    private function userOwnsAttribute(User $user, Attribute $attribute): bool
    {
        if (! $attribute->site) {
            return false;
        }

        return $this->userOwnsSite($user, $attribute->site);
    }

    /**
     * Check if user owns the site.
     */
    private function userOwnsSite(User $user, Site $site): bool
    {
        return $site->user_id === $user->id;
    }
}

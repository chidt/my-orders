<?php

namespace App\Policies;

use App\Models\Tag;
use App\Models\User;

class TagPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_tags') || $user->can('manage_tags');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Tag $tag): bool
    {
        return ($user->can('view_tags') || $user->can('manage_tags'))
            && $this->belongsToUserSite($user, $tag);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create_tags') || $user->can('manage_tags');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Tag $tag): bool
    {
        return ($user->can('update_tags') || $user->can('manage_tags'))
            && $this->belongsToUserSite($user, $tag);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Tag $tag): bool
    {
        return ($user->can('delete_tags') || $user->can('manage_tags'))
            && $this->belongsToUserSite($user, $tag);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Tag $tag): bool
    {
        return $user->can('manage_tags')
            && $this->belongsToUserSite($user, $tag);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Tag $tag): bool
    {
        return $user->can('manage_tags')
            && $this->belongsToUserSite($user, $tag);
    }

    /**
     * Determine whether the user can merge tags.
     */
    public function merge(User $user): bool
    {
        return $user->can('merge_tags') || $user->can('manage_tags');
    }

    /**
     * Check if tag belongs to user's site.
     */
    private function belongsToUserSite(User $user, Tag $tag): bool
    {
        return $user->site_id && $tag->site_id === $user->site_id;
    }
}

<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\Site;
use App\Models\User;

class CustomerPolicy
{
    public function viewAny(User $user, ?Site $site = null): bool
    {
        if (! $user->can('manage_customers')) {
            return false;
        }

        if ($site) {
            return $this->userOwnsSite($user, $site);
        }

        return true;
    }

    public function view(User $user, Customer $customer): bool
    {
        return $user->can('view_customers') && $this->userOwnsCustomer($user, $customer);
    }

    public function create(User $user, ?Site $site = null): bool
    {
        if (! $user->can('create_customers')) {
            return false;
        }

        if ($site) {
            return $this->userOwnsSite($user, $site);
        }

        return $user->site_id !== null;
    }

    public function update(User $user, Customer $customer): bool
    {
        return $user->can('edit_customers') && $this->userOwnsCustomer($user, $customer);
    }

    public function delete(User $user, Customer $customer): bool
    {
        return $user->can('delete_customers') && $this->userOwnsCustomer($user, $customer);
    }

    private function userOwnsCustomer(User $user, Customer $customer): bool
    {
        if (! $customer->site) {
            return false;
        }

        return $this->userOwnsSite($user, $customer->site);
    }

    private function userOwnsSite(User $user, Site $site): bool
    {
        return $site->user_id === $user->id;
    }
}

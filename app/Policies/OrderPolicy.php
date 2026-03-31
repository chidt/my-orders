<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\Site;
use App\Models\User;

class OrderPolicy
{
    public function viewAny(User $user, ?Site $site = null): bool
    {
        if (! $user->can('manage_orders')) {
            return false;
        }

        if ($site) {
            return $this->userOwnsSite($user, $site);
        }

        return true;
    }

    public function create(User $user, ?Site $site = null): bool
    {
        if (! $user->can('manage_orders')) {
            return false;
        }

        if ($site) {
            return $this->userOwnsSite($user, $site);
        }

        return $user->site_id !== null;
    }

    public function view(User $user, Order $order, Site $site): bool
    {
        if (! $user->can('manage_orders')) {
            return false;
        }

        if ((int) $order->site_id !== (int) $site->id) {
            return false;
        }

        return $this->userOwnsSite($user, $site);
    }

    public function update(User $user, Order $order, Site $site): bool
    {
        if (! $user->can('manage_orders')) {
            return false;
        }

        if ((int) $order->site_id !== (int) $site->id) {
            return false;
        }

        return $this->userOwnsSite($user, $site);
    }

    public function delete(User $user, Order $order, Site $site): bool
    {
        if (! $user->can('manage_orders')) {
            return false;
        }

        if ((int) $order->site_id !== (int) $site->id) {
            return false;
        }

        return $this->userOwnsSite($user, $site);
    }

    private function userOwnsSite(User $user, Site $site): bool
    {
        return $site->user_id === $user->id;
    }
}

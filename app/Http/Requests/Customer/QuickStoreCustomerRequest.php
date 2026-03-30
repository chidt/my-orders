<?php

namespace App\Http\Requests\Customer;

use App\Models\Order;
use App\Models\Site;

class QuickStoreCustomerRequest extends StoreCustomerRequest
{
    public function authorize(): bool
    {
        /** @var Site $site */
        $site = $this->route('site');

        return $this->user()->can('viewAny', [Order::class, $site]);
    }
}

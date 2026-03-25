<?php

namespace App\Actions\Customer;

use App\Models\Customer;
use DomainException;
use Illuminate\Support\Facades\DB;

class DeleteCustomer
{
    public function execute(Customer $customer): void
    {
        if ($customer->orders()->exists()) {
            throw new DomainException('Không thể xóa khách hàng đã có đơn hàng.');
        }

        DB::transaction(function () use ($customer): void {
            $customer->addresses()->delete();
            $customer->delete();
        });
    }
}

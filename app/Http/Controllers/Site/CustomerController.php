<?php

namespace App\Http\Controllers\Site;

use App\Actions\Customer\DeleteCustomer;
use App\Actions\Customer\ListCustomers;
use App\Actions\Customer\StoreCustomer;
use App\Actions\Customer\UpdateCustomer;
use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\StoreCustomerRequest;
use App\Http\Requests\Customer\UpdateCustomerRequest;
use App\Models\Customer;
use App\Models\Province;
use App\Models\Site;
use App\Models\Ward;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    public function index(Request $request, Site $site, ListCustomers $action): Response
    {
        Gate::authorize('viewAny', [Customer::class, $site]);

        $filters = [
            'search' => (string) $request->query('search', ''),
            'type' => (string) $request->query('type', ''),
            'province_id' => (string) $request->query('province_id', ''),
            'ward_id' => (string) $request->query('ward_id', ''),
            'sort_by' => (string) $request->query('sort_by', 'name'),
            'sort_direction' => (string) $request->query('sort_direction', 'asc'),
        ];

        if ($filters['province_id'] === '' && $filters['ward_id'] !== '') {
            $ward = Ward::query()->select('province_id')->find((int) $filters['ward_id']);
            if ($ward) {
                $filters['province_id'] = (string) $ward->province_id;
            }
        }

        $customers = $action->execute($site, $filters);

        $customers->getCollection()->transform(function (Customer $customer) {
            $customer->setAttribute('can_delete', ($customer->orders_count ?? 0) === 0);

            return $customer;
        });

        $statistics = [
            'total' => Customer::query()->forSite($site->id)->count(),
            'individual' => Customer::query()->forSite($site->id)->where('type', 1)->count(),
            'business' => Customer::query()->forSite($site->id)->where('type', 2)->count(),
        ];

        return Inertia::render('site/customers/Index', [
            'site' => $site->only(['id', 'name', 'slug']),
            'customers' => $customers,
            'statistics' => $statistics,
            'filters' => $filters,
            'customerTypes' => Customer::typeOptions(),
            'provinces' => Province::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function create(Site $site): Response
    {
        Gate::authorize('create', [Customer::class, $site]);

        return Inertia::render('site/customers/Create', [
            'site' => $site->only(['id', 'name', 'slug']),
            'customerTypes' => Customer::typeOptions(),
            'provinces' => Province::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(StoreCustomerRequest $request, Site $site, StoreCustomer $action): RedirectResponse
    {
        Gate::authorize('create', [Customer::class, $site]);

        $action->execute($request->validated(), $site);

        return redirect()
            ->route('site.customers.index', $site)
            ->with('success', 'Khách hàng đã được tạo thành công.');
    }

    public function edit(Site $site, Customer $customer): Response
    {
        Gate::authorize('update', $customer);

        $customer->load('addresses.ward.province');
        $defaultAddress = $customer->defaultAddress();

        return Inertia::render('site/customers/Edit', [
            'site' => $site->only(['id', 'name', 'slug']),
            'customerTypes' => Customer::typeOptions(),
            'provinces' => Province::query()->orderBy('name')->get(['id', 'name']),
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'phone' => $customer->phone,
                'email' => $customer->email,
                'type' => $customer->type?->value ?? $customer->type,
                'description' => $customer->description,
                'site_id' => $customer->site_id,
                'address' => $defaultAddress?->address,
                'ward_id' => $defaultAddress?->ward_id,
                'province_id' => $defaultAddress?->ward?->province_id,
                'addresses' => $customer->addresses->map(fn ($address) => [
                    'id' => $address->id,
                    'address' => $address->address,
                    'ward_id' => $address->ward_id,
                    'province_id' => $address->ward?->province_id,
                    'is_default' => $address->is_default,
                ])->values(),
                'created_at' => $customer->created_at,
                'updated_at' => $customer->updated_at,
            ],
        ]);
    }

    public function update(UpdateCustomerRequest $request, Site $site, Customer $customer, UpdateCustomer $action): RedirectResponse
    {
        Gate::authorize('update', $customer);

        $action->execute($customer, $request->validated());

        return redirect()
            ->route('site.customers.index', $site)
            ->with('success', 'Khách hàng đã được cập nhật thành công.');
    }

    public function destroy(Site $site, Customer $customer, DeleteCustomer $action): RedirectResponse
    {
        Gate::authorize('delete', $customer);

        try {
            $action->execute($customer);

            return redirect()
                ->route('site.customers.index', $site)
                ->with('success', 'Khách hàng đã được xóa thành công.');
        } catch (\DomainException $exception) {
            return redirect()
                ->route('site.customers.index', $site)
                ->with('error', $exception->getMessage());
        }
    }
}

<?php

namespace App\Http\Requests\Customer;

use App\Enums\CustomerType;
use App\Models\Customer;
use App\Models\Site;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Site $site */
        $site = $this->route('site');

        return $this->user()->can('create', [Customer::class, $site]);
    }

    public function rules(): array
    {
        /** @var Site $site */
        $site = $this->route('site');

        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'regex:/^(0\d{9,10})$/'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('customers', 'email')->where(fn ($query) => $query->where('site_id', $site->id)),
            ],
            'type' => ['required', 'integer', Rule::in(array_keys(Customer::typeOptions()))],
            'description' => ['nullable', 'string'],

            // Backward-compatible single address fields
            'address' => ['nullable', 'string'],
            'ward_id' => ['nullable', 'integer', 'exists:wards,id'],

            // New multiple addresses payload
            'addresses' => [
                'nullable',
                'array',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $type = (int) $this->input('type');

                    if (empty($value)) {
                        if (! $this->filled('address') || ! $this->filled('ward_id')) {
                            $fail('Vui lòng nhập ít nhất một địa chỉ.');
                        }

                        return;
                    }

                    if ($type === CustomerType::INDIVIDUAL->value && count($value) > 1) {
                        $fail('Khách hàng cá nhân chỉ được có một địa chỉ.');
                    }

                    if ($type === CustomerType::BUSINESS->value) {
                        $defaultCount = collect($value)->filter(
                            fn ($item) => filter_var($item['is_default'] ?? false, FILTER_VALIDATE_BOOLEAN)
                        )->count();

                        if ($defaultCount !== 1) {
                            $fail('Doanh nghiệp phải chọn đúng 1 địa chỉ mặc định.');
                        }
                    }
                },
            ],
            'addresses.*.address' => ['required_with:addresses', 'string', 'max:500'],
            'addresses.*.ward_id' => ['required_with:addresses', 'integer', 'exists:wards,id'],
            'addresses.*.is_default' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.regex' => 'Số điện thoại phải bắt đầu bằng số 0 và có 10-11 số.',
            'email.unique' => 'Email đã tồn tại trong site hiện tại.',
            'addresses.*.address.required_with' => 'Địa chỉ không được để trống.',
            'addresses.*.ward_id.required_with' => 'Phường/Xã không được để trống.',
        ];
    }
}

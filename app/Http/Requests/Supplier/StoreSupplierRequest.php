<?php

namespace App\Http\Requests\Supplier;

use App\Models\Site;
use App\Models\Supplier;
use Illuminate\Foundation\Http\FormRequest;

class StoreSupplierRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        /** @var Site $site */
        $site = $this->route('site');

        return $this->user()->can('create', [Supplier::class, $site]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'person_in_charge' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50', 'regex:/^(\+?\d{7,15})$/'],
            'address' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Tên nhà cung cấp là bắt buộc.',
            'name.string' => 'Tên nhà cung cấp phải là chuỗi ký tự.',
            'name.max' => 'Tên nhà cung cấp không được vượt quá :max ký tự.',
            'person_in_charge.string' => 'Người phụ trách phải là chuỗi ký tự.',
            'person_in_charge.max' => 'Người phụ trách không được vượt quá :max ký tự.',
            'phone.string' => 'Số điện thoại phải là chuỗi ký tự.',
            'phone.max' => 'Số điện thoại không được vượt quá :max ký tự.',
            'phone.regex' => 'Số điện thoại không đúng định dạng.',
            'address.string' => 'Địa chỉ phải là chuỗi ký tự.',
            'description.string' => 'Mô tả phải là chuỗi ký tự.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => 'tên nhà cung cấp',
            'person_in_charge' => 'người phụ trách',
            'phone' => 'số điện thoại',
            'address' => 'địa chỉ',
            'description' => 'mô tả',
        ];
    }
}

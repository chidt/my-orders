<?php

namespace App\Http\Requests\Warehouse;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWarehouseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $site = $this->route('site');

        return $this->user()->can('create', [\App\Models\Warehouse::class, $site]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $site = $this->route('site');

        return [
            'code' => [
                'required',
                'string',
                'max:50',
                'regex:/^[A-Z0-9-]+$/',
                Rule::unique('warehouses')->where(function ($query) use ($site) {
                    return $query->where('site_id', $site->id);
                }),
            ],
            'name' => 'required|string|max:255',
            'address' => 'required|string',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'code.required' => 'Mã kho là bắt buộc.',
            'code.string' => 'Mã kho phải là chuỗi ký tự.',
            'code.max' => 'Mã kho không được vượt quá :max ký tự.',
            'code.regex' => 'Mã kho chỉ được chứa chữ cái in hoa, số và dấu gạch ngang.',
            'code.unique' => 'Mã kho đã tồn tại trong trang web này.',
            'name.required' => 'Tên kho là bắt buộc.',
            'name.string' => 'Tên kho phải là chuỗi ký tự.',
            'name.max' => 'Tên kho không được vượt quá :max ký tự.',
            'address.required' => 'Địa chỉ kho là bắt buộc.',
            'address.string' => 'Địa chỉ kho phải là chuỗi ký tự.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'code' => 'mã kho',
            'name' => 'tên kho',
            'address' => 'địa chỉ kho',
        ];
    }
}

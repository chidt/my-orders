<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductTypeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('manage_product_types');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $productType = $this->route('product_type');

        return [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('product_types')->where(function ($query) {
                    return $query->where('site_id', $this->user()->site_id);
                })->ignore($productType->id),
            ],
            'order' => 'integer|min:0',
            'show_on_front' => 'boolean',
            'color' => ['nullable', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Tên loại sản phẩm là bắt buộc.',
            'name.string' => 'Tên loại sản phẩm phải là chuỗi ký tự.',
            'name.max' => 'Tên loại sản phẩm không được vượt quá :max ký tự.',
            'name.unique' => 'Tên loại sản phẩm đã tồn tại trong hệ thống.',
            'order.integer' => 'Thứ tự phải là số nguyên.',
            'order.min' => 'Thứ tự không được nhỏ hơn 0.',
            'show_on_front.boolean' => 'Hiển thị trang chủ phải là true/false.',
            'color.regex' => 'Màu sắc phải có định dạng hex hợp lệ (ví dụ: #ff0000).',
        ];
    }
}

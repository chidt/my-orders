<?php

namespace App\Http\Requests\Attribute;

use App\Models\Attribute;
use App\Models\Site;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAttributeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        /** @var Attribute $attribute */
        $attribute = $this->route('attribute');

        return $this->user()->can('update', $attribute);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Site $site */
        $site = $this->route('site');

        /** @var Attribute $attribute */
        $attribute = $this->route('attribute');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('attributes')->where('site_id', $site->id)->ignore($attribute->id),
            ],
            'code' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(-[a-z0-9]+)*$/',
                Rule::unique('attributes')->where('site_id', $site->id)->ignore($attribute->id),
            ],
            'description' => ['nullable', 'string'],
            'order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Tên thuộc tính là bắt buộc.',
            'name.string' => 'Tên thuộc tính phải là chuỗi ký tự.',
            'name.max' => 'Tên thuộc tính không được vượt quá :max ký tự.',
            'name.unique' => 'Tên thuộc tính đã tồn tại trong site này.',
            'code.required' => 'Mã thuộc tính là bắt buộc.',
            'code.string' => 'Mã thuộc tính phải là chuỗi ký tự.',
            'code.max' => 'Mã thuộc tính không được vượt quá :max ký tự.',
            'code.regex' => 'Mã thuộc tính phải là lowercase, dạng kebab-case (ví dụ: product-type).',
            'code.unique' => 'Mã thuộc tính đã tồn tại trong site này.',
            'description.string' => 'Mô tả phải là chuỗi ký tự.',
            'order.integer' => 'Thứ tự phải là số nguyên.',
            'order.min' => 'Thứ tự phải lớn hơn hoặc bằng :min.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => 'tên thuộc tính',
            'code' => 'mã thuộc tính',
            'description' => 'mô tả',
            'order' => 'thứ tự',
        ];
    }
}

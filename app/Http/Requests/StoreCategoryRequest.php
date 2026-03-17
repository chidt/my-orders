<?php

namespace App\Http\Requests;

use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create_categories') || $this->user()->can('manage_categories');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $siteId = auth()->user()->site_id;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categories')
                    ->where('site_id', $siteId),
            ],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^[a-z0-9-]+$/',
                Rule::unique('categories')
                    ->where('site_id', $siteId),
            ],
            'description' => [
                'nullable',
                'string',
                'max:2000',
            ],
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id')->where(function ($query) use ($siteId) {
                    $query->where('site_id', $siteId);
                }),
            ],
            'order' => [
                'nullable',
                'integer',
                'min:0',
                'max:999999',
            ],
            'is_active' => [
                'nullable',
                'boolean',
            ],
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Tên danh mục là bắt buộc.',
            'name.unique' => 'Tên danh mục đã tồn tại trong cửa hàng này.',
            'name.max' => 'Tên danh mục không được vượt quá 255 ký tự.',
            'slug.unique' => 'Đường dẫn (slug) đã tồn tại.',
            'slug.regex' => 'Đường dẫn chỉ được chứa chữ cài thường, số và dấu gạch ngang.',
            'description.max' => 'Mô tả không được vượt quá 2000 ký tự.',
            'parent_id.exists' => 'Danh mục cha không tồn tại.',
            'order.min' => 'Thứ tự phải lớn hơn hoặc bằng 0.',
            'order.max' => 'Thứ tự không được vượt quá 999999.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->parent_id) {
                $parentCategory = Category::find($this->parent_id);
                if ($parentCategory && $parentCategory->depth >= 2) {
                    $validator->errors()->add('parent_id', 'Danh mục không thể tạo sâu hơn 3 cấp.');
                }
            }
        });
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convert empty strings to null
        if ($this->parent_id === '') {
            $this->merge(['parent_id' => null]);
        }

        // Set default values
        $this->merge([
            'is_active' => $this->boolean('is_active', true),
            'order' => $this->integer('order', 0),
        ]);
    }
}

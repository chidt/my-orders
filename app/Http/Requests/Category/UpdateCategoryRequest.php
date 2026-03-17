<?php

namespace App\Http\Requests\Category;

use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $category = $this->route('category');

        // If category is a string (ID), we need to load the model for site_id check
        if (is_string($category)) {
            $category = Category::find($category);
        }

        // Check if user has permission
        if (! ($this->user()->can('update_categories') || $this->user()->can('manage_categories'))) {
            return false;
        }

        // If category doesn't exist or belongs to different site, let controller handle 404
        if (! $category || $category->site_id !== auth()->user()->site_id) {
            // Don't return false here, let the controller handle the 404
            return true;
        }

        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $category = $this->route('category');
        $siteId = auth()->user()->site_id;

        // Get category ID, handling both string and object cases
        $categoryId = null;
        if ($category) {
            $categoryId = is_object($category) ? $category->id : $category;
        }

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categories')
                    ->where('site_id', $siteId)
                    ->ignore($categoryId),
            ],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^[a-z0-9-]+$/',
                Rule::unique('categories')
                    ->where('site_id', $siteId)
                    ->ignore($categoryId),
            ],
            'description' => [
                'nullable',
                'string',
                'max:2000',
            ],
            'parent_id' => [
                'nullable',
                'integer',
                'different:id',
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
            'slug.regex' => 'Đường dẫn chỉ được chứa chữ cải thường, số và dấu gạch ngang.',
            'description.max' => 'Mô tả không được vượt quá 2000 ký tự.',
            'parent_id.exists' => 'Danh mục cha không tồn tại.',
            'parent_id.different' => 'Danh mục không thể là cha của chính nó.',
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

                // Check depth limit
                if ($parentCategory && $parentCategory->depth >= 2) {
                    $validator->errors()->add('parent_id', 'Danh mục không thể di chuyển sâu hơn 3 cấp.');
                }

                // Check circular reference
                $categoryId = $this->get('id'); // Set in prepareForValidation
                if ($categoryId && $parentCategory) {
                    $currentCategory = Category::find($categoryId);
                    if ($currentCategory && $parentCategory->isDescendantOf($currentCategory)) {
                        $validator->errors()->add('parent_id', 'Không thể tạo tham chiếu vòng tròn trong cấu trúc danh mục.');
                    }
                }
            }
        });
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $category = $this->route('category');

        // Convert empty strings to null
        if ($this->parent_id === '') {
            $this->merge(['parent_id' => null]);
        }

        // Add category ID for validation
        if ($category) {
            $categoryId = is_object($category) ? $category->id : $category;
            $this->merge(['id' => $categoryId]);
        }

        // Ensure is_active is boolean
        if ($this->has('is_active')) {
            $this->merge(['is_active' => $this->boolean('is_active')]);
        }
    }
}

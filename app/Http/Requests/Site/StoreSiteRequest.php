<?php

namespace App\Http\Requests\Site;

use App\Models\Site;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSiteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9-]+$/',
                Rule::unique(Site::class),
            ],
            'description' => ['nullable', 'string', 'max:2000'],
            'settings' => ['nullable', 'array'],
        ];
    }

    /**
     * Get custom error messages for validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Tên trang web là bắt buộc.',
            'name.string' => 'Tên trang web phải là chuỗi ký tự.',
            'name.max' => 'Tên trang web không được vượt quá :max ký tự.',

            'slug.required' => 'Slug trang web là bắt buộc.',
            'slug.string' => 'Slug trang web phải là chuỗi ký tự.',
            'slug.max' => 'Slug trang web không được vượt quá :max ký tự.',
            'slug.regex' => 'Slug chỉ được chứa chữ cái thường, số và dấu gạch ngang.',
            'slug.unique' => 'Slug này đã được sử dụng. Vui lòng chọn slug khác.',

            'description.string' => 'Mô tả phải là chuỗi ký tự.',
            'description.max' => 'Mô tả không được vượt quá :max ký tự.',
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTagRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create_tags') || $this->user()->can('manage_tags');
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
                'max:100',
                Rule::unique('tags')
                    ->where('site_id', $siteId),
            ],
            'slug' => [
                'nullable',
                'string',
                'max:100',
                'regex:/^[a-z0-9-]+$/',
                Rule::unique('tags')
                    ->where('site_id', $siteId),
            ],
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Tên thẻ là bắt buộc.',
            'name.unique' => 'Tên thẻ đã tồn tại trong cửa hàng này.',
            'name.max' => 'Tên thẻ không được vượt quá 100 ký tự.',
            'slug.unique' => 'Đường dẫn (slug) đã tồn tại.',
            'slug.regex' => 'Đường dẫn chỉ được chứa chữ cải thường, số và dấu gạch ngang.',
            'slug.max' => 'Đường dẫn không được vượt quá 100 ký tự.',
        ];
    }
}

<?php

namespace App\Http\Requests\Settings;

use App\Concerns\ProfileValidationRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ProfileUpdateRequest extends FormRequest
{
    use ProfileValidationRules;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->profileRules($this->user()->id);
    }

    /**
     * Get custom error messages for validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Tên là bắt buộc.',
            'name.string' => 'Tên phải là một chuỗi ký tự.',
            'name.max' => 'Tên không được vượt quá :max ký tự.',

            'email.required' => 'Email là bắt buộc.',
            'email.string' => 'Email phải là một chuỗi ký tự.',
            'email.email' => 'Định dạng email không hợp lệ.',
            'email.max' => 'Email không được vượt quá :max ký tự.',
            'email.unique' => 'Email này đã được sử dụng.',

            'phone_number.string' => 'Số điện thoại phải là một chuỗi ký tự.',
            'phone_number.regex' => 'Định dạng số điện thoại không hợp lệ.',
            'phone_number.max' => 'Số điện thoại không được vượt quá :max ký tự.',
            'phone_number.unique' => 'Số điện thoại này đã được sử dụng.',

            // Site validation messages
            'site.name.required' => 'Tên trang web là bắt buộc.',
            'site.name.string' => 'Tên trang web phải là một chuỗi ký tự.',
            'site.name.max' => 'Tên trang web không được vượt quá :max ký tự.',

            'site.slug.required' => 'Slug trang web là bắt buộc.',
            'site.slug.string' => 'Slug trang web phải là một chuỗi ký tự.',
            'site.slug.max' => 'Slug trang web không được vượt quá :max ký tự.',
            'site.slug.regex' => 'Slug chỉ được chứa chữ cái thường, số và dấu gạch ngang.',
            'site.slug.unique' => 'Slug này đã được sử dụng.',

            'site.description.string' => 'Mô tả trang web phải là một chuỗi ký tự.',
            'site.description.max' => 'Mô tả trang web không được vượt quá :max ký tự.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'tên',
            'email' => 'email',
            'phone_number' => 'số điện thoại',
            'site.name' => 'tên trang web',
            'site.slug' => 'slug trang web',
            'site.description' => 'mô tả trang web',
        ];
    }
}

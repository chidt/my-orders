<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UpdateRoleRequest extends FormRequest
{
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
                'min:2',
                'max:255',
                Rule::unique(Role::class, 'name')
                    ->where('guard_name', 'web')
                    ->ignore($this->route('role')),
            ],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => [
                'integer',
                Rule::exists(Permission::class, 'id'),
            ],
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
            'name.required' => 'Tên vai trò là bắt buộc.',
            'name.min' => 'Tên vai trò phải có ít nhất 2 ký tự.',
            'name.max' => 'Tên vai trò không được vượt quá 255 ký tự.',
            'name.unique' => 'Tên vai trò này đã tồn tại.',
            'permissions.array' => 'Quyền hạn phải là một mảng.',
            'permissions.*.integer' => 'Mỗi quyền hạn phải là một ID hợp lệ.',
            'permissions.*.exists' => 'Một hoặc nhiều quyền hạn được chọn không hợp lệ.',
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
            'name' => 'tên vai trò',
            'permissions' => 'quyền hạn',
        ];
    }
}

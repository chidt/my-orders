<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Spatie\Permission\Models\Permission;

class UpdatePermissionRequest extends FormRequest
{
    public function rules(): array
    {
        $permissionId = $this->route('permission') instanceof Permission
            ? $this->route('permission')->id
            : $this->route('permission');

        return [
            'name' => ['required', 'string', 'max:255', 'unique:permissions,name,'.$permissionId],
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
            'name.required' => 'Tên quyền hạn là bắt buộc.',
            'name.string' => 'Tên quyền hạn phải là một chuỗi ký tự.',
            'name.max' => 'Tên quyền hạn không được vượt quá 255 ký tự.',
            'name.unique' => 'Tên quyền hạn này đã tồn tại.',
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
            'name' => 'tên quyền hạn',
        ];
    }
}

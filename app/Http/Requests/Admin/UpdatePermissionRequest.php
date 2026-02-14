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

    public function messages(): array
    {
        return [
            'name.required' => 'The permission name is required.',
            'name.unique' => 'This permission name already exists.',
        ];
    }
}

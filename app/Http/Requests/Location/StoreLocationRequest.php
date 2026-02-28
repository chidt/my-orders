<?php

namespace App\Http\Requests\Location;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLocationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $warehouse = $this->route('warehouse');

        return $this->user()->can('create', [\App\Models\Location::class, $warehouse]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $warehouse = $this->route('warehouse');

        return [
            'code' => [
                'required',
                'string',
                'max:50',
                'regex:/^[A-Z0-9-]+$/',
                Rule::unique('locations')->where(function ($query) use ($warehouse) {
                    return $query->where('warehouse_id', $warehouse->id);
                }),
            ],
            'name' => 'required|string|max:255',
            'is_default' => 'nullable|boolean',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'code.required' => 'Mã vị trí là bắt buộc.',
            'code.string' => 'Mã vị trí phải là chuỗi ký tự.',
            'code.max' => 'Mã vị trí không được vượt quá 50 ký tự.',
            'code.regex' => 'Mã vị trí chỉ được chứa chữ cái in hoa, số và dấu gạch ngang.',
            'code.unique' => 'Mã vị trí đã tồn tại trong kho này.',
            'name.required' => 'Tên vị trí là bắt buộc.',
            'name.string' => 'Tên vị trí phải là chuỗi ký tự.',
            'name.max' => 'Tên vị trí không được vượt quá 255 ký tự.',
            'is_default.boolean' => 'Giá trị vị trí mặc định không hợp lệ.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'code' => 'mã vị trí',
            'name' => 'tên vị trí',
            'is_default' => 'vị trí mặc định',
        ];
    }
}

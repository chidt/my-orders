<?php

namespace App\Http\Requests\Order;

use App\Enums\OrderStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkUpdateOrderDetailStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order_detail_ids' => ['nullable', 'array'],
            'order_detail_ids.*' => ['integer', 'exists:order_details,id'],
            'filter_status' => [
                'nullable',
                'integer',
                Rule::enum(OrderStatus::class),
            ],
            'status' => ['required', 'integer', Rule::enum(OrderStatus::class)],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $ids = array_values(array_filter(array_map('intval', (array) $this->input('order_detail_ids', []))));
            if (count($ids) === 0 && ! $this->filled('filter_status')) {
                $validator->errors()->add(
                    'order_detail_ids',
                    'Vui lòng chọn chi tiết đơn hàng hoặc dùng lọc theo trạng thái (filter_status).',
                );
            }
        });
    }

    public function messages(): array
    {
        return [
            'order_detail_ids.array' => 'Danh sách chi tiết đơn hàng không hợp lệ.',
            'order_detail_ids.*.exists' => 'Chi tiết đơn hàng đã chọn không tồn tại.',
            'filter_status.enum' => 'Trạng thái lọc không hợp lệ.',
            'status.required' => 'Vui lòng chọn trạng thái cần cập nhật.',
            'status.enum' => 'Trạng thái cập nhật không hợp lệ.',
            'note.max' => 'Ghi chú không được vượt quá 1000 ký tự.',
        ];
    }
}

<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'shipping_address_id' => ['required', 'integer', 'exists:addresses,id'],
            'order_date' => ['nullable', 'date'],
            'sale_channel' => ['required', 'integer', 'in:1,2,3'],
            'shipping_payer' => ['required', 'integer', 'in:1,2'],
            'shipping_note' => ['nullable', 'string'],
            'order_note' => ['nullable', 'string'],
            'details' => ['required', 'array', 'min:1'],
            'details.*.product_item_id' => ['required', 'integer', 'exists:product_items,id'],
            'details.*.qty' => ['required', 'integer', 'min:1'],
            'details.*.discount' => ['nullable', 'numeric', 'min:0'],
            'details.*.addition_price' => ['nullable', 'numeric', 'min:0'],
            'details.*.note' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'customer_id.required' => 'Vui lòng chọn khách hàng.',
            'customer_id.exists' => 'Khách hàng không hợp lệ.',
            'shipping_address_id.required' => 'Vui lòng chọn địa chỉ giao hàng.',
            'shipping_address_id.exists' => 'Địa chỉ giao hàng không hợp lệ.',
            'sale_channel.required' => 'Vui lòng chọn kênh bán hàng.',
            'sale_channel.in' => 'Kênh bán hàng không hợp lệ.',
            'shipping_payer.required' => 'Vui lòng chọn người thanh toán vận chuyển.',
            'shipping_payer.in' => 'Người thanh toán vận chuyển không hợp lệ.',
            'details.required' => 'Đơn hàng phải có ít nhất 1 sản phẩm.',
            'details.array' => 'Danh sách sản phẩm không hợp lệ.',
            'details.min' => 'Đơn hàng phải có ít nhất 1 sản phẩm.',
            'details.*.product_item_id.required' => 'Vui lòng chọn sản phẩm cho từng dòng.',
            'details.*.product_item_id.exists' => 'Sản phẩm đã chọn không tồn tại.',
            'details.*.qty.required' => 'Vui lòng nhập số lượng cho từng sản phẩm.',
            'details.*.qty.min' => 'Số lượng sản phẩm phải lớn hơn 0.',
            'details.*.discount.min' => 'Chiết khấu không được nhỏ hơn 0.',
            'details.*.addition_price.min' => 'Phí bổ sung không được nhỏ hơn 0.',
        ];
    }
}

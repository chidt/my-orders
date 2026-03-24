<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage_products');
    }

    public function rules(): array
    {
        $siteId = (int) $this->user()->site_id;
        $productId = (int) $this->route('product');

        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('products', 'code')->ignore($productId),
            ],
            'description' => ['nullable', 'string'],
            'supplier_code' => ['nullable', 'string', 'max:255'],

            'main_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,gif,webp', 'max:10240'],
            'slide_images' => ['nullable', 'array', 'max:10'],
            'slide_images.*' => ['image', 'mimes:jpg,jpeg,png,gif,webp', 'max:10240'],
            'remove_main_image' => ['nullable', 'boolean'],
            'remove_slide_media_ids' => ['nullable', 'array'],
            'remove_slide_media_ids.*' => ['integer'],
            'variant_images' => ['nullable', 'array'],
            'variant_images.*.key' => ['required_with:variant_images', 'string', 'max:255'],
            'variant_images.*.media_id' => ['nullable', 'integer'],
            'variant_images.*.slide_index' => ['nullable', 'integer', 'min:0'],
            'variant_images.*.use_main_image' => ['nullable', 'boolean'],
            'variant_image_files' => ['nullable', 'array'],
            'variant_image_files.*' => ['image', 'mimes:jpg,jpeg,png,gif,webp', 'max:10240'],
            'variant_image_file_keys' => ['nullable', 'array'],
            'variant_image_file_keys.*' => ['string', 'max:255'],

            'category_id' => [
                'required',
                'integer',
                Rule::exists('categories', 'id')->where(fn ($q) => $q->where('site_id', $siteId)),
            ],
            'supplier_id' => [
                'required',
                'integer',
                Rule::exists('suppliers', 'id')->where(fn ($q) => $q->where('site_id', $siteId)),
            ],
            'unit_id' => ['required', 'integer', Rule::exists('units', 'id')],
            'product_type_id' => [
                'required',
                'integer',
                Rule::exists('product_types', 'id')->where(fn ($q) => $q->where('site_id', $siteId)),
            ],
            'default_location_id' => [
                'required',
                'integer',
                Rule::exists('locations', 'id')->where(function ($q) use ($siteId) {
                    $q->whereIn('warehouse_id', function ($sub) use ($siteId) {
                        $sub->select('id')->from('warehouses')->where('site_id', $siteId);
                    });
                }),
            ],

            'qty_in_stock' => ['nullable', 'integer', 'min:0'],
            'weight' => ['nullable', 'numeric', 'min:0'],
            'price' => ['required', 'numeric', 'gt:0'],
            'partner_price' => ['nullable', 'numeric', 'min:0', 'lte:price'],
            'purchase_price' => ['required', 'numeric', 'gt:0'],
            'order_closing_date' => ['nullable', 'date'],
            'extra_attributes' => ['nullable', 'array'],

            'tags' => ['nullable', 'array'],
            'tags.*' => [
                'integer',
                Rule::exists('tags', 'id')->where(fn ($q) => $q->where('site_id', $siteId)),
            ],

            'attributes' => ['nullable', 'array'],
            'attributes.*.attribute_id' => [
                'required_with:attributes',
                'integer',
                Rule::exists('attributes', 'id')->where(fn ($q) => $q->where('site_id', $siteId)),
            ],
            'attributes.*.values' => ['required_with:attributes', 'array', 'min:1'],
            'attributes.*.values.*.code' => ['required', 'string', 'max:50'],
            'attributes.*.values.*.value' => ['required', 'string', 'max:255'],
            'attributes.*.values.*.order' => ['required', 'integer', 'min:0'],
            'attributes.*.values.*.addition_value' => ['nullable', 'numeric', 'min:0'],
            'attributes.*.values.*.partner_addition_value' => ['nullable', 'numeric', 'min:0'],
            'attributes.*.values.*.purchase_addition_value' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Tên sản phẩm là bắt buộc.',
            'main_image.required' => 'Ảnh chính là bắt buộc.',
            'main_image.image' => 'Ảnh chính phải là hình ảnh hợp lệ.',
            'main_image.mimes' => 'Ảnh chính chỉ chấp nhận định dạng: jpg, jpeg, png, gif, webp.',
            'slide_images.*.mimes' => 'Ảnh slide chỉ chấp nhận định dạng: jpg, jpeg, png, gif, webp.',
            'variant_image_files.*.mimes' => 'Ảnh biến thể chỉ chấp nhận định dạng: jpg, jpeg, png, gif, webp.',
            'category_id.required' => 'Danh mục là bắt buộc.',
            'supplier_id.required' => 'Nhà cung cấp là bắt buộc.',
            'unit_id.required' => 'Đơn vị tính là bắt buộc.',
            'product_type_id.required' => 'Loại sản phẩm là bắt buộc.',
            'default_location_id.required' => 'Vị trí mặc định là bắt buộc.',
            'price.gt' => 'Giá bán phải lớn hơn 0.',
            'purchase_price.gt' => 'Giá nhập phải lớn hơn 0.',
            'partner_price.lte' => 'Giá đối tác phải nhỏ hơn hoặc bằng giá bán.',
            'attributes.*.values.min' => 'Mỗi thuộc tính phải có ít nhất 1 giá trị.',
            'attributes.array' => 'Thuộc tính phải là danh sách.',
            'attributes.*.attribute_id.required_with' => 'Vui lòng chọn thuộc tính.',
            'attributes.*.attribute_id.exists' => 'Thuộc tính không hợp lệ.',
            'attributes.*.values.required_with' => 'Vui lòng nhập giá trị cho thuộc tính.',
            'attributes.*.values.array' => 'Danh sách giá trị thuộc tính không hợp lệ.',
            'attributes.*.values.*.code.required' => 'Mã giá trị là bắt buộc.',
            'attributes.*.values.*.code.max' => 'Mã giá trị không được vượt quá :max ký tự.',
            'attributes.*.values.*.value.required' => 'Tên giá trị là bắt buộc.',
            'attributes.*.values.*.value.max' => 'Tên giá trị không được vượt quá :max ký tự.',
            'attributes.*.values.*.order.required' => 'Thứ tự là bắt buộc.',
            'attributes.*.values.*.order.integer' => 'Thứ tự phải là số nguyên.',
            'attributes.*.values.*.order.min' => 'Thứ tự không được nhỏ hơn 0.',
            'attributes.*.values.*.addition_value.numeric' => 'Phụ phí phải là số.',
            'attributes.*.values.*.addition_value.min' => 'Phụ phí không được nhỏ hơn 0.',
            'attributes.*.values.*.partner_addition_value.numeric' => 'Phụ phí giá đối tác phải là số.',
            'attributes.*.values.*.partner_addition_value.min' => 'Phụ phí giá đối tác không được nhỏ hơn 0.',
            'attributes.*.values.*.purchase_addition_value.numeric' => 'Phụ phí giá nhập phải là số.',
            'attributes.*.values.*.purchase_addition_value.min' => 'Phụ phí giá nhập không được nhỏ hơn 0.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $mainImage = $this->file('main_image');
            $removeMainImage = $this->boolean('remove_main_image');

            // If marking to remove main image, must have a new main image to upload
            if ($removeMainImage && ! $mainImage) {
                $validator->errors()->add('main_image', 'Ảnh chính là bắt buộc.');
                return;
            }

            // Original attributes validation
            /** @var array<int, array{attribute_id:int, values: array<int, array{code:string, value:string, order:int}>}>|null $attributes */
            $attributes = $this->input('attributes');

            if (! is_array($attributes) || count($attributes) === 0) {
                return;
            }

            $totalCombinations = 1;
            $allValueCodes = [];

            foreach ($attributes as $attribute) {
                $values = $attribute['values'] ?? [];
                if (! is_array($values) || count($values) === 0) {
                    $validator->errors()->add('attributes', 'Mỗi thuộc tính phải có ít nhất 1 giá trị.');

                    return;
                }

                $totalCombinations *= count($values);

                foreach ($values as $value) {
                    if (isset($value['code'])) {
                        $allValueCodes[] = (string) $value['code'];
                    }
                }
            }

            if ($totalCombinations > 100) {
                $validator->errors()->add('attributes', 'Quá nhiều biến thể (> 100). Vui lòng giảm số lượng giá trị thuộc tính.');
            }

            $normalized = array_map(fn ($c) => strtoupper(trim($c)), $allValueCodes);
            if (count($normalized) !== count(array_unique($normalized))) {
                $validator->errors()->add('attributes', 'Mã thuộc tính phải duy nhất trong phạm vi sản phẩm.');
            }
        });
    }
}

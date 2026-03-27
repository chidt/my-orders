<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;

class SyncChildProductsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage_products');
    }

    public function rules(): array
    {
        return [
            'variant_images' => ['nullable', 'array'],
            'variant_images.*.key' => ['required_with:variant_images', 'string', 'max:255'],
            'variant_images.*.media_id' => ['nullable', 'integer'],
            'variant_images.*.slide_index' => ['nullable', 'integer', 'min:0'],
            'variant_images.*.use_main_image' => ['nullable', 'boolean'],
            'variant_image_files' => ['nullable', 'array'],
            'variant_image_files.*' => ['image', 'mimes:jpg,jpeg,png,gif,webp', 'max:10240'],
            'variant_image_file_keys' => ['nullable', 'array'],
            'variant_image_file_keys.*' => ['string', 'max:255'],
        ];
    }
}

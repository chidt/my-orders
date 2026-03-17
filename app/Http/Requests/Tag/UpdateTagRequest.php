<?php

namespace App\Http\Requests\Tag;

use App\Models\Tag;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTagRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $tag = $this->route('tag');

        // If tag is a string (ID), we need to load the model for site_id check
        if (is_string($tag)) {
            $tag = Tag::find($tag);
        }

        // Check if user has permission
        if (! ($this->user()->can('update_tags') || $this->user()->can('manage_tags'))) {
            return false;
        }

        // If tag doesn't exist or belongs to different site, let controller handle 404
        if (! $tag || $tag->site_id !== auth()->user()->site_id) {
            // Don't return false here, let the controller handle the 404
            return true;
        }

        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $tag = $this->route('tag');
        $siteId = auth()->user()->site_id;

        // Get tag ID, handling both string and object cases
        $tagId = null;
        if ($tag) {
            $tagId = is_object($tag) ? $tag->id : $tag;
        }

        return [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('tags')
                    ->where('site_id', $siteId)
                    ->ignore($tagId),
            ],
            'slug' => [
                'nullable',
                'string',
                'max:100',
                'regex:/^[a-z0-9-]+$/',
                Rule::unique('tags')
                    ->where('site_id', $siteId)
                    ->ignore($tagId),
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

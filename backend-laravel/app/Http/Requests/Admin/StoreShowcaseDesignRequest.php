<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreShowcaseDesignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'         => ['required', 'string'],
            'description'   => ['required', 'string'],
            'prompt'        => ['required', 'string'],
            'image_base64'  => ['required', 'string'],
            'clothing_type' => ['required', 'string'],
            'color'         => ['nullable', 'string'],
            'template_id'   => ['nullable', 'string'],
            'tags'          => ['nullable', 'array'],
            'is_featured'   => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'يرجى إدخال جميع الحقول المطلوبة',
        ];
    }
}

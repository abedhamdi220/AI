<?php

namespace App\Http\Requests\Designs;

use Illuminate\Foundation\Http\FormRequest;

class PreviewDesignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
             'prompt'            => ['required', 'string'],
            'clothing_type'     => ['required', 'string'],
            'color'             => ['nullable', 'string'],
            // حماية للـ Base64
            'logo_base64'       => ['nullable', 'string', 'max:10000000'],
            'logo_position'     => ['nullable', 'string'],
            'user_photo_base64' => ['nullable', 'string', 'max:10000000'],
            'view_angle'        => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'يرجى إدخال الوصف ونوع الملبس',
        ];
    }
}

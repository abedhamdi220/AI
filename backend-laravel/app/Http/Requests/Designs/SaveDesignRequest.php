<?php

namespace App\Http\Requests\Designs;

use Illuminate\Foundation\Http\FormRequest;

class SaveDesignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
          'prompt'            => ['required', 'string'],
            // حماية للـ Base64
            'image_base64'      => ['required', 'string', 'max:10000000'],
            'clothing_type'     => ['required', 'string'],
            'template_id'       => ['nullable', 'string'],
            'color'             => ['nullable', 'string'],
            'phone_number'      => ['nullable', 'string'],
            'user_photo_base64' => ['nullable', 'string', 'max:10000000'],
            'logo_base64'       => ['nullable', 'string', 'max:10000000'],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'يرجى إدخال جميع البيانات المطلوبة',
        ];
    }
}

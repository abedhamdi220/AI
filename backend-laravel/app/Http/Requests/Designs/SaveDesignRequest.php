<?php

namespace App\Http\Requests\Designs;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class SaveDesignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'detail' => $validator->errors()->first(),
        ], 422));
    }

    public function rules(): array
    {
        return [
          'prompt'            => ['required', 'string', 'max:1000'],
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

<?php

namespace App\Http\Requests\Designs;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class PreviewDesignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    // نحافظ على شكل استجابة الخطأ الموحّد في التطبيق ({"detail": "..."})
    // بدل شكل Laravel الافتراضي ({"message": "...", "errors": {...}})
    // حتى لا تنكسر الواجهة التي تقرأ error.response.data.detail
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'detail' => $validator->errors()->first(),
        ], 422));
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

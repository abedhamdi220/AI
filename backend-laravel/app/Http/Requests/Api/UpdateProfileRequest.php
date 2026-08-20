<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true; // مسموح لأننا نستخدم middleware(auth:customer)
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'date_of_birth' => 'nullable|date|before:today',
            'gender' => 'nullable|in:male,female,other',
            'preferences' => 'nullable|array',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048', // 2MB Max
        ];
    }

    /**
     * رسائل الخطأ المخصصة
     */
    public function messages()
    {
        return [
            'avatar.image' => 'يجب أن يكون الملف المرفق صورة.',
            'avatar.mimes' => 'الصيغ المدعومة للصورة هي: jpeg, png, jpg, webp.',
            'avatar.max' => 'حجم الصورة يجب أن لا يتجاوز 2 ميجابايت.',
            'gender.in' => 'الجنس يجب أن يكون ذكر أو أنثى.',
        ];
    }
}

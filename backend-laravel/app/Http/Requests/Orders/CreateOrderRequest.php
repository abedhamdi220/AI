<?php

namespace App\Http\Requests\Orders;

use Illuminate\Foundation\Http\FormRequest;

class CreateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
          'design_image_base64' => ['required', 'string', 'max:10000000'],
            'prompt'              => ['required', 'string'],
            'phone_number'        => ['required', 'string'],
            'size'                => ['nullable', 'string'],
            // تم تغيير string إلى uuid بناءً على بنية قاعدة البيانات لديك
            'design_id'           => ['nullable', 'uuid'],
            'color'               => ['nullable', 'string'],
            'coupon_code'         => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'يرجى إدخال جميع البيانات المطلوبة',
        ];
    }
}

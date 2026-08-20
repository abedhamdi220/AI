<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class ToggleWishlistRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
         return [
            'product_id' => 'required|uuid|exists:products,id',
        ];
    }

    public function messages()
    {
        return [
            'product_id.required' => 'معرف المنتج مطلوب.',
            'product_id.uuid' => 'معرف المنتج يجب أن يكون بصيغة UUID.',
            'product_id.exists' => 'معرف المنتج غير موجود.',
        ];
    }
}

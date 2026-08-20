<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class AddToCartRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
         return [
            'product_id' => 'required|uuid|exists:products,id',
            // قد يكون المنتج بدون خصائص (Variant) لذلك نجعله nullable
            'product_variant_id' => 'nullable|uuid|exists:product_variants,id',
            'quantity' => 'required|integer|min:1|max:100',
        ];
    }

    public function messages()
    {
        return [
            'product_id.required' => 'معرف المنتج مطلوب.',
            'product_id.uuid' => 'معرف المنتج يجب أن يكون بصيغة UUID.',
            'product_id.exists' => 'معرف المنتج غير موجود.',
            'product_variant_id.uuid' => 'معرف خصائص المنتج يجب أن يكون بصيغة UUID.',
            'product_variant_id.exists' => 'معرف خصائص المنتج غير موجود.',
            'quantity.required' => 'الكمية مطلوبة.',
            'quantity.integer' => 'الكمية يجب أن تكون عددًا صحيحًا.',
            'quantity.min' => 'الكمية لا يمكن أن تكون أقل من 1.',
            'quantity.max' => 'الكمية لا يمكن أن تكون أكثر من 100.',

        ];
    }
}

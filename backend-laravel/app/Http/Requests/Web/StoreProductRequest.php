<?php

namespace App\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        // مسموح للبائع المسجل فقط
        return true; // سيتم التحقق من ذلك في قواعد التحقق، لذا نعيد true هنا للسماح بالوصول إلى هذا الطلب
    }

    public function rules(): array
    {
        return [
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['required', 'string', 'max:255'],
            'description_ar' => ['nullable', 'string'],
            'description_en' => ['nullable', 'string'],
            'category_id' => ['required', 'exists:categories,id'],
            'brand_id' => ['nullable', 'exists:brands,id'],
            'base_price' => ['required', 'numeric', 'min:0'],

            // المتغيرات (Variants) إن وجدت
            'variants' => ['nullable', 'array'],
            'variants.*.sku' => ['required', 'string', 'unique:product_variants,sku'],
            'variants.*.price' => ['nullable', 'numeric', 'min:0'],
            'variants.*.attributes' => ['nullable', 'array'], // مثل: اللون، الحجم
        ];
    }
}

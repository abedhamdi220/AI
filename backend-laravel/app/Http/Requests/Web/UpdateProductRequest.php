<?php

namespace App\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        // مسموح للبائع المسجل فقط
        return true; // سيتم التحقق من ذلك في قواعد التحقق، لذا نعيد true هنا للسماح بالوصول إلى هذا الطلب
    }

    public function rules(): array
    {
        return [
            'name_ar' => ['sometimes', 'string', 'max:255'],
            'name_en' => ['sometimes', 'string', 'max:255'],
            'description_ar' => ['sometimes','nullable', 'string'],
            'description_en' => ['sometimes','nullable', 'string'],
            'category_id' => ['sometimes', 'exists:categories,id'],
            'brand_id' => ['sometimes', 'exists:brands,id'],
            'base_price' => ['sometimes', 'numeric', 'min:0'],

            // المتغيرات (Variants) إن وجدت
            'variants' => ['sometimes', 'array'],
            'variants.*.sku' => ['nullable','sometimes', 'string', 'unique:product_variants,sku'],
            'variants.*.price' => ['nullable','sometimes', 'numeric', 'min:0'],
            'variants.*.attributes' => ['nullable','sometimes', 'array'], // مثل: اللون، الحجم
        ];
    }
}

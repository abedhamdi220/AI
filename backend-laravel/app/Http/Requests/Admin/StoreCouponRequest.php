<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreCouponRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
         'code' => 'required|string|unique:coupons,code|max:20',
            'discount_percentage' => 'required|numeric|min:1|max:100',
            'max_uses' => 'nullable|integer|min:1',
            'expiry_date' => 'nullable|date|after:today',
            'is_active' => 'boolean'
        ];
    }

    public function messages(): array
    {
        return [
            'code.required'                => 'يرجى إدخال كود الكوبون ونسبة الخصم',
            'discount_percentage.required' => 'يرجى إدخال كود الكوبون ونسبة الخصم',
            'code.unique'                  => 'كود الكوبون موجود بالفعل',
        ];
    }
}

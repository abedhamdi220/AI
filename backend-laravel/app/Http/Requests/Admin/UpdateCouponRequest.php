<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCouponRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'is_active' => 'sometimes|boolean',
            'expiry_date' => 'nullable|date',
            'max_uses' => 'nullable|integer|min:1',
            'discount_percentage' => 'sometimes|numeric|min:1|max:100',
        ];
    }
}

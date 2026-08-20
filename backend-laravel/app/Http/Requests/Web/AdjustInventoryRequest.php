<?php

namespace App\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdjustInventoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // مسموح للبائع المسجل فقط، سيتم التحقق من ذلك في قواعد التحقق
    }

    public function rules(): array
    {
        return [
            // يجب أن يكون المستودع تابعاً لهذا البائع
            'warehouse_id' => [
                'required',
                Rule::exists('addresses', 'id')->where(function ($query) {
                    return $query->where('addressable_id', auth('vendor')->id())
                                 ->where('type', 'warehouse');
                }),
            ],
            // يجب أن يكون المتغير (Variant) تابعاً لمنتج يملكه هذا البائع
            'product_variant_id' => ['required', 'exists:product_variants,id'],
            'type' => ['required', 'in:add,remove'], // إضافة أو سحب من المخزون
            'quantity' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}

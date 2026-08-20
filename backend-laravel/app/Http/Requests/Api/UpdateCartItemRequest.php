<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCartItemRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
       return [
            'quantity' => 'required|integer|min:1|max:100',
        ];
    }

    public function messages()
    {
        return [
                'quantity.required' => 'الكمية مطلوبة.',
                'quantity.integer' => 'الكمية يجب أن تكون عددًا صحيحًا.',
                'quantity.min' => 'الكمية لا يمكن أن تكون أقل من 1.',
                'quantity.max' => 'الكمية لا يمكن أن تكون أكثر من 100.',

        ];
    }
}

<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class SendOtpRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'identifier' => 'required|string', // يمكن أن يكون بريد إلكتروني أو رقم هاتف
        ];
    }

    public function messages()
    {
        return [
            'identifier.required' => 'حقل البريد الإلكتروني أو رقم الهاتف مطلوب.',
        ];
    }
}

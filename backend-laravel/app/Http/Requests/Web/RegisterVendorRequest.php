<?php

namespace App\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;

class RegisterVendorRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }
    public function rules()
    {
        return [
            'business_name' => 'required|string|max:255',
            'email' => 'required|email|unique:vendors,email',
            'password' => 'required|string|min:8|confirmed',
            'business_license_file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120', // الحد الأقصى 5 ميجا
            'tax_number' => 'nullable|string|max:50',
        ];
    }
}

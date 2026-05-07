<?php
namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
class ResetPasswordRequest extends FormRequest
{
    public function authorize() { return true; }
    public function rules()
    {
        return [
            'identifier' => 'required|string', // البريد أو الهاتف
            'otp' => 'required|numeric|digits:6',
            'password' => 'required|string|min:8|confirmed',
        ];
    }
}

<?php
namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize() { return true; }
    public function rules()
    {
        return [
            'identifier' => 'required|string', // يمكن أن يكون email أو phone
            'password' => 'required|string',
        ];
    }
}

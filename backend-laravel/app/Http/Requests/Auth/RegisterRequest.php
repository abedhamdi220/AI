<?php
namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'username' => ['required', 'string', 'unique:users,username'],
            'email'    => [
                'required',
                'email',
                'unique:users,email',
                'regex:/^[a-zA-Z0-9._%+-]+@gmail\.com$/i'
            ],
            'password' => [
                'required',
                'string',
                'min:6',
                'regex:/[a-zA-Z]/', // يجب أن تحتوي على أحرف
                'regex:/[0-9]/'     // يجب أن تحتوي على أرقام
            ],
        ];
    }

    public function messages(): array
    {
        return [

        ];
    }
}

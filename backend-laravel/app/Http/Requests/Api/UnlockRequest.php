<?php
namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
class UnlockRequest extends FormRequest
{
    public function authorize() { return true; }
    public function rules()
    {
        return [
            'password' => 'required|string',
        ];
    }
}

<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserLimitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'designs_limit' => ['nullable', 'integer', 'min:0'],
            'is_unlimited'  => ['nullable', 'boolean'],
        ];
    }
}

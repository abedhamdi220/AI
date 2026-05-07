<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FilterNotificationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // مسموح لجميع المستخدمين المسجلين
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): bool|array
    {
        return [
            'search'   => 'nullable|string|max:255',
            'filter'   => 'nullable|in:all,read,unread',
            'per_page' => 'nullable|integer|min:1|max:100',
        ];
    }

    /**
     * Custom messages for validation (Optional)
     */
    public function messages(): array
    {
        return [
            'filter.in' => 'يجب أن يكون الفلتر أحد القيم التالية: الكل، مقروء، غير مقروء.',
        ];
    }
}

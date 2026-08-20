<?php
namespace App\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest{
      public function authorize()
    {
        return true; // مسموح لأننا نستخدم middleware(auth:customer)
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'date_of_birth' => 'nullable|date|before:today',
            'gender' => 'nullable|in:male,female,other',
            'preferences' => 'nullable|array',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048', // 2MB Max
        ];
    }
}

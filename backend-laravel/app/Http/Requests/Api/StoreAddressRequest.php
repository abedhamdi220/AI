<?php

namespace App\Http\Requests\Address;

use Illuminate\Foundation\Http\FormRequest;

class StoreAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        // السماح دائماً لأننا نتحقق من المستخدم في الـ Controller بواسطة الـ Auth Middleware
        return true;
    }

    public function rules(): array
    {
        $supported_locales = config("app.supported_locales", ['en', 'ar']);

        $rules = [
            'city_id'          => ['required', 'uuid', 'exists:cities,id'],
            'title'            => ['nullable', 'string', 'max:100'],
            'area'             => ['nullable', 'string', 'max:100'],
            'building_number'  => ['nullable', 'string', 'max:50'],
            'floor_number'     => ['nullable', 'string', 'max:50'],
            'apartment_number' => ['nullable', 'string', 'max:50'],
            'landmark'         => ['nullable', 'string', 'max:255'],
            'latitude'         => ['nullable', 'numeric'],
            'longitude'        => ['nullable', 'numeric'],
            'is_default'       => ['nullable', 'boolean'],
            'type'             => ['nullable', 'string', 'in:shipping,billing'],
            'street'           => ['required', 'array'],
            'notes'            => ['nullable', 'array'],
        ];

        // دعم الترجمة لحقول الشارع والملاحظات (بناءً على طلبك السابق)
        foreach ($supported_locales as $locale) {
            $rules['street.' . $locale] = ['required', 'string', 'max:255'];
            $rules['notes.' . $locale]  = ['nullable', 'string', 'max:1000'];
        }

        return $rules;
    }
}

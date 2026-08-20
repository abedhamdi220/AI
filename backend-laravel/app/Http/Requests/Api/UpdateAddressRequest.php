<?php

namespace App\Http\Requests\Address;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        $address = $this->route('address');
        $user = auth()->user(); // يجلب المستخدم الحالي سواء كان Customer أو Vendor

        // التحقق من أن العنوان يتبع للمستخدم الحالي (مهم جداً للأمان)
        return $address && $address->addressable_id === $user->id;
    }

    public function rules(): array
    {
        $supported_locales = config("app.supported_locales", ['en', 'ar']);

        // نستخدم sometimes لجعل الحقول اختيارية أثناء التحديث إذا لم يتم إرسالها
        $rules = [
            'city_id'          => ['sometimes', 'uuid', 'exists:cities,id'],
            'title'            => ['sometimes', 'nullable', 'string', 'max:100'],
            'area'             => ['sometimes', 'nullable', 'string', 'max:100'],
            'building_number'  => ['sometimes', 'nullable', 'string', 'max:50'],
            'floor_number'     => ['sometimes', 'nullable', 'string', 'max:50'],
            'apartment_number' => ['sometimes', 'nullable', 'string', 'max:50'],
            'landmark'         => ['sometimes', 'nullable', 'string', 'max:255'],
            'latitude'         => ['sometimes', 'nullable', 'numeric'],
            'longitude'        => ['sometimes', 'nullable', 'numeric'],
            'is_default'       => ['sometimes', 'boolean'],
            'type'             => ['sometimes', 'string', 'in:shipping,billing'],
            'street'           => ['sometimes', 'array'],
            'notes'            => ['sometimes', 'nullable', 'array'],
        ];

        foreach ($supported_locales as $locale) {
            $rules['street.' . $locale] = ['sometimes', 'required', 'string', 'max:255'];
            $rules['notes.' . $locale]  = ['sometimes', 'nullable', 'string', 'max:1000'];
        }

        return $rules;
    }
}

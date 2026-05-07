<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AddressResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $locale = app()->getLocale();

        $streetTranslations = $this->street ?? [];
        $notesTranslations = $this->notes ?? [];

        return [
            'id' => $this->id,
            'title' => $this->title,
            'type' => $this->type,

            // الترجمات
            'street' => $streetTranslations[$locale] ?? ($streetTranslations['en'] ?? ''),
            'notes' => $notesTranslations[$locale] ?? ($notesTranslations['en'] ?? ''),

            // تفاصيل المبنى
            'area' => $this->area,
            'building_number' => $this->building_number,
            'floor_number' => $this->floor_number,
            'apartment_number' => $this->apartment_number,
            'landmark' => $this->landmark,

            'is_default' => (bool) $this->is_default,

            'coordinates' => [
                'latitude' => $this->latitude ? (float) $this->latitude : null,
                'longitude' => $this->longitude ? (float) $this->longitude : null,
            ],

            // العلاقات
            'city' => new CityResource($this->whenLoaded('city')),
            'country' => new CountryResource($this->whenLoaded('country')),

            // من يملك العنوان؟ (اختياري، يفيد في لوحة تحكم الإدارة)
            'owner_type' => class_basename($this->addressable_type),
            'owner_id' => $this->addressable_id,

            'created_at' => $this->created_at?->format("Y-m-d H:i:s"),
        ];
    }
}

<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $locale = app()->getLocale();
        $nameTranslations = $this->name ?? [];

        return [
            'id' => $this->id,
            'name' => $nameTranslations[$locale] ?? ($nameTranslations['en'] ?? ''),
            // جلب الدولة إذا تم تحميلها عبر (Eager Loading) باستخدام with()
            'country' => new CountryResource($this->whenLoaded('country')),
        ];
    }
}

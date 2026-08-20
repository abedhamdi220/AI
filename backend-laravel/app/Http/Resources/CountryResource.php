<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CountryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // جلب لغة التطبيق الحالية (مثلاً: ar أو en)
        $locale = app()->getLocale();

        // قراءة المصفوفة من الموديل، وإذا كانت فارغة نعيد مصفوفة فارغة
        $nameTranslations = $this->name ?? [];

        return [
            'id' => $this->id,
            'code' => $this->code,
            // إرجاع الاسم باللغة الحالية، وإذا لم يوجد نعيد الإنجليزي كافتراضي
            'name' => $nameTranslations[$locale] ?? ($nameTranslations['en'] ?? ''),
            'is_default' => (bool) $this->is_default,
        ];
    }
}

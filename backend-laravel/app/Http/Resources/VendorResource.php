<?php

namespace App\Http\Resources;

use App\Http\Resources\Api\ProfileResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Vendor Resource
 * تحويل موديل Vendor إلى JSON response موحد
 */
class VendorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'business_name' => $this->business_name,
            'email' => $this->email,
            'description' => $this->description,
            'status' => $this->status,
            'is_featured' => $this->is_featured,
            'commission_rate' => $this->commission_rate,
            'verified_at' => $this->verified_at,
            'business_license' => $this->business_license,
            'tax_number' => $this->tax_number,
            'contact_info' => [
                'whatsapp' => $this->whatsapp_number,
                'facebook' => $this->facebook_page,
                'instagram' => $this->instagram_handle,
            ],
            'profile' => new ProfileResource($this->whenLoaded('profile')),
            'products_count' => $this->products_count ?? $this->products()->count(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

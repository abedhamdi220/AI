<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DesignResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'prompt' => $this->prompt,
            'image_url' => $this->image_url, // تم استخدام الرابط بدل Base64
            'composite_image_url' => $this->composite_image_url, // تم إضافة صورة الدمج
            'clothing_type' => $this->clothing_type,
            'template_id' => $this->template_id,
            'color' => $this->color,
            'phone_number' => $this->phone_number,
            'is_favorite' => (bool) $this->is_favorite,
            'created_at' => $this->created_at,

            // Relationships
            'user' => new UserResource($this->whenLoaded('user')),
        ];
    }
}

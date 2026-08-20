<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'design_id' => $this->design_id,
            // تم إزالة Base64 (يمكن للواجهة الأمامية أخذ الصورة من كائن الـ design بالأسفل)
            'prompt' => $this->prompt,
            'phone_number' => $this->phone_number,
            'size' => $this->size,
            'color' => $this->color,
            'price' => (float) $this->price,
            'discount' => (float) $this->discount,
            'final_price' => (float) $this->final_price,
            'coupon_code' => $this->coupon_code,
            'status' => $this->status,
            'created_at' => $this->created_at,

            // Relationships
            'user' => new UserResource($this->whenLoaded('user')),
            'design' => new DesignResource($this->whenLoaded('design')),
        ];
    }
}

<?php

namespace App\Http\Resources;

use App\Http\Resources\Admin\CouponResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CouponUsageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'coupon_id' => $this->coupon_id,
            'coupon_code' => $this->coupon_code,
            'user_id' => $this->user_id,
            'order_id' => $this->order_id,
            'used_at' => $this->used_at,

            // Relationships (Loaded conditionally)
            'user' => new UserResource($this->whenLoaded('user')),
            'coupon' => new CouponResource($this->whenLoaded('coupon')),
            'order' => new OrderResource($this->whenLoaded('order')),
        ];
    }
}

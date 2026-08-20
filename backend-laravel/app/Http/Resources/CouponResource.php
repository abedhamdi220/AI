<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CouponResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
           'id' => $this->id,
            'code' => $this->code,
            'discount_percentage' => (float) $this->discount_percentage,
            'expiry_date' => $this->expiry_date,
            'is_active' => (bool) $this->is_active,
            'max_uses' => $this->max_uses,
            'current_uses' => (int) $this->current_uses,
            'created_at' => $this->created_at,
        ];
    }
}

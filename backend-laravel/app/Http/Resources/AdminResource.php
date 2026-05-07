<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Admin Resource
 * تحويل موديل Admin إلى JSON response موحد
 */
class AdminResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'is_super_admin' => $this->is_super_admin,
            'is_active' => $this->is_active,
            'department' => $this->department,
            'profile' => new ProfileResource($this->whenLoaded('profile')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

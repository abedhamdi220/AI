<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'username' => $this->username,
            'email' => $this->email,
            'is_admin' => (bool) $this->is_admin,
            'designs_limit' => (int) $this->designs_limit,
            'designs_used' => (int) $this->designs_used,
            'is_unlimited' => (bool) $this->is_unlimited,
            'email_verified' => (bool) $this->email_verified,
            'created_at' => $this->created_at,
        ];
    }
}

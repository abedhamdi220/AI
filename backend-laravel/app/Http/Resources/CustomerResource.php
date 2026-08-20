<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'phone' => $this->phone,
            'status' => $this->status,
            // جلب بيانات البروفايل إذا تم عمل Load لها
            'profile' => $this->whenLoaded('profile', function () {
                return [
                    'first_name' => $this->profile->first_name,
                    'last_name' => $this->profile->last_name,
                    'full_name' => $this->profile->first_name . ' ' . $this->profile->last_name,
                ];
            }),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}

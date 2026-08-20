<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Resources\Json\JsonResource;

class ProfileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->full_name, // قادمة من Accessor في الموديل
            'date_of_birth' => $this->date_of_birth ? $this->date_of_birth->format('Y-m-d') : null,
            'gender' => $this->gender,
            'avatar' => $this->avatar_url, // قادمة من Accessor الذي يستخدم Spatie
            'preferences' => $this->preferences,
            'is_complete' => $this->isComplete(),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}

<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShowcaseDesignResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'prompt' => $this->prompt,
            'image_url' => $this->image_url, // استخدام الرابط من الموديل
            'clothing_type' => $this->clothing_type,
            'color' => $this->color,
            'template_id' => $this->template_id,
            'tags' => $this->tags ?? [],
            'likes_count' => (int) $this->likes_count,
            'is_featured' => (bool) $this->is_featured,
            'is_active' => (bool) $this->is_active,
            'display_order' => (int) $this->display_order,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Relationships
            'design' => new DesignResource($this->whenLoaded('design')),
        ];
    }
}

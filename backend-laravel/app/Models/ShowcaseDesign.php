<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class ShowcaseDesign extends Model
{
    protected $fillable = [
       'title',
        'description',
        'prompt',
        'image_base64',
        'image_path',
        'clothing_type',
        'color',
        'template_id',
        'tags',
        'likes_count',
        'is_featured',
        'is_active',
        'design_id',
        'display_order',
    ];

   protected $casts = [
        'tags' => 'array',
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
        'display_order' => 'integer',
    ];


    public function getImageUrlAttribute()
    {

        if ($this->image_path) {
            return asset('storage/' . $this->image_path);
        }
        return $this->design ? $this->design->image_url : null;
    }

    public function design()
    {
        return $this->belongsTo(Design::class, 'design_id', 'id');
    }
}

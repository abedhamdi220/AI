<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Design extends Model
{
    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'user_id',
        'prompt',
        'image_base64',
        'clothing_type',
        'template_id',
        'color',
        'phone_number',
        'user_photo_base64',
        'logo_base64',
        'is_favorite',
    ];

    protected $hidden = ['_id'];

    protected $casts = [
         'is_favorite' => 'boolean',
         'created_at' => 'datetime',
    ];

    public function user() { return $this->belongsTo(User::class, 'user_id', 'id'); }
    public function order() { return $this->hasOne(Order::class, 'design_id', 'id'); }
}

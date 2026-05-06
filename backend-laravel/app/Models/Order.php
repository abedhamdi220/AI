<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Order extends Model
{
    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'user_id',
        'design_id',
        'design_image_base64',
        'prompt',
        'phone_number',
        'size',
        'color',
        'price',
        'discount',
        'final_price',
        'coupon_code',
        'status',
    ];

    protected $hidden = ['_id'];

    protected $casts = [
         'created_at' => 'datetime',
    ];

    public function user() { return $this->belongsTo(User::class, 'user_id', 'id'); }
    public function design() { return $this->belongsTo(Design::class, 'design_id', 'id'); }
}

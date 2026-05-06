<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class CouponUsage extends Model
{
    public $timestamps = false;

    protected $collection = 'coupon_usages';
protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'id',
        'coupon_id',
        'coupon_code',
        'user_id',
        'order_id',
        'used_at',
    ];

    protected $casts = [
        'used_at' => 'datetime',
    ];
}

<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Coupon extends Model
{
    public $timestamps = false;

    protected $collection = 'coupons';
protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;
 protected $fillable = [
        'id',
        'code',
        'discount_percentage',
        'expiry_date',
        'is_active',
        'max_uses',
        'current_uses',
        'description',
        'min_purchase',
        'created_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'discount_percentage' => 'float',
        'max_uses' => 'integer',
        'current_uses' => 'integer',
        'expiry_date' => 'datetime',
        'created_at' => 'datetime',
    ];
}

<?php

namespace App\Models;

use MongoDB\Laravel\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use MongoDB\Laravel\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable, SoftDeletes;
 protected $collection = 'users';
 protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
          'id',
        'username',
        'email',
        'password',
        'is_admin',
        'designs_limit',
        'designs_used',
        'is_unlimited',
        'email_verified',
        'verification_code',
        'verification_code_expires',
        'created_at',
    ];

    protected $hidden = [
        'password',
        'verification_code',
        '_id',
    ];

    protected function casts(): array
    {
     return [
            'is_admin' => 'boolean',
            'is_unlimited' => 'boolean',
            'email_verified' => 'boolean',
            'verification_code_expires' => 'datetime',
            'created_at' => 'datetime',
            'designs_limit' => 'integer',
            'designs_used' => 'integer',

        ];
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [
            'is_admin' => $this->is_admin
        ];
    }

    public function designs()
    {
        return $this->hasMany(Design::class);
    }
    public function orders()
    {
        return $this->hasMany(Order::class);
    }
    public function couponUsages()
    {
        return $this->hasMany(CouponUsage::class);
    }
}

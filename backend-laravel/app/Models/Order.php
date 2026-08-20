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
        'showcase_design_id',   // في حال كان الطلب لنفس تصميم جاهز من المعرض
        'design_image_base64', // مبقى عليه للتوافق مع البيانات القديمة
        'design_image_path',   // الحقل الجديد لمسار صورة الطلب
        'prompt',
        'phone_number',
        'size',
        'color',
        'clothing_type',
        'price',
        'discount',
        'final_price',
        'coupon_code',
        'status',
    ];

    // إخفاء حقل Base64 الطويل لتسريع جلب الطلبات
    protected $hidden = [
        '_id',
        'design_image_base64'
    ];

    // إرفاق الرابط الجاهز للصورة
    protected $appends = [
        'design_image_url'
    ];

    protected $casts = [
         'created_at' => 'datetime',
    ];

    // دالة استرجاع رابط صورة الطلب
    public function getDesignImageUrlAttribute()
    {
        if ($this->design_image_path) {
            return asset('storage/' . $this->design_image_path);
        }
        // توافق رجعي للطلبات القديمة
        if ($this->design_image_base64) {
             $prefix = str_contains($this->design_image_base64, 'data:image') ? '' : 'data:image/png;base64,';
             return $prefix . $this->design_image_base64;
        }
        return null;
    }

    public function user() { return $this->belongsTo(User::class, 'user_id', 'id'); }
    public function design() { return $this->belongsTo(Design::class, 'design_id', 'id'); }
}

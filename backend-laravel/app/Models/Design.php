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
        'image_base64',      // مبقى عليه للتوافق مع البيانات القديمة
        'image_path',        // الحقل الجديد لمسار الصورة
        'clothing_type',
        'template_id',
        'color',
        'phone_number',
        'user_photo_base64', // مبقى عليه للتوافق مع البيانات القديمة
        'user_photo_path',   // الحقل الجديد لمسار صورة المستخدم
        'logo_base64',       // مبقى عليه للتوافق مع البيانات القديمة
        'logo_path',         // الحقل الجديد لمسار الشعار
        'is_favorite',
    ];

    // إخفاء حقول Base64 من استجابة الـ API لتقليل استهلاك الذاكرة وتسريع النظام
    protected $hidden = [
        '_id',
        'image_base64',
        'user_photo_base64',
        'logo_base64'
    ];

    // إضافة الروابط تلقائياً عند استدعاء الموديل
    protected $appends = [
        'image_url',
        'user_photo_url',
        'logo_url'
    ];

    protected $casts = [
         'is_favorite' => 'boolean',
         'created_at' => 'datetime',
    ];

    // دالة استرجاع رابط الصورة الأساسية
    public function getImageUrlAttribute()
    {
        if ($this->image_path) {
            return asset('storage/' . $this->image_path);
        }
        // توافق رجعي: إذا كانت الصورة قديمة ومحفوظة كـ Base64
        if ($this->image_base64) {
             $prefix = str_contains($this->image_base64, 'data:image') ? '' : 'data:image/png;base64,';
             return $prefix . $this->image_base64;
        }
        return null;
    }

    // دالة استرجاع رابط صورة المستخدم
    public function getUserPhotoUrlAttribute()
    {
        if ($this->user_photo_path) {
            return asset('storage/' . $this->user_photo_path);
        }
        if ($this->user_photo_base64) {
             $prefix = str_contains($this->user_photo_base64, 'data:image') ? '' : 'data:image/png;base64,';
             return $prefix . $this->user_photo_base64;
        }
        return null;
    }

    // دالة استرجاع رابط الشعار
    public function getLogoUrlAttribute()
    {
        if ($this->logo_path) {
            return asset('storage/' . $this->logo_path);
        }
        if ($this->logo_base64) {
             $prefix = str_contains($this->logo_base64, 'data:image') ? '' : 'data:image/png;base64,';
             return $prefix . $this->logo_base64;
        }
        return null;
    }

    public function user() { return $this->belongsTo(User::class, 'user_id', 'id'); }
    public function order() { return $this->hasOne(Order::class, 'design_id', 'id'); }
}

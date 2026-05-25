<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class ShowcaseDesign extends Model
{
    protected $fillable = [
        'title',
        'description',
        'prompt',
        'image_base64', // مبقى عليه للبيانات القديمة
        'image_path',   // موجود مسبقاً وسيتم الاعتماد عليه
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

    // إخفاء كود الصورة الطويل من الـ API
    protected $hidden = [
        'image_base64'
    ];

    // إضافة الرابط كمتغير إضافي للواجهة
    protected $appends = [
        'image_url'
    ];

    protected $casts = [
        'tags' => 'array',
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
        'display_order' => 'integer',
    ];

    // دالة استرجاع الصورة
    public function getImageUrlAttribute()
    {
        // 1. الأولوية للملف المحفوظ في السيرفر
        if ($this->image_path) {
            return asset('storage/' . $this->image_path);
        }
        // 2. إذا كان مرتبطاً بتصميم، جلب الصورة من التصميم
        if ($this->design && $this->design->image_url) {
            return $this->design->image_url;
        }
        // 3. الحل الأخير للبيانات القديمة جداً
        if ($this->image_base64) {
             $prefix = str_contains($this->image_base64, 'data:image') ? '' : 'data:image/png;base64,';
             return $prefix . $this->image_base64;
        }
        return null;
    }

    public function design()
    {
        return $this->belongsTo(Design::class, 'design_id', 'id');
    }
}

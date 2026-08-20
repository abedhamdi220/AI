<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Notification extends Model
{
    public $timestamps = false;

    protected $collection = 'notifications';
protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'id',
        'user_id',
        'title',
        'message',
        'type',
        'is_read',
        'created_at',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'created_at' => 'datetime',
    ];


    public static function createNotification($userId, $title, $message, $type = 'info')
    {
        try {
            self::create([
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'user_id' => $userId,
                'title' => $title,
                'message' => $message,
                'type' => $type,
                'is_read' => false,
                'created_at' => now(),
            ]);
        } catch (\Exception $error) {
            \Log::error('Create Notification Error: ' . $error->getMessage());
        }
    }
}

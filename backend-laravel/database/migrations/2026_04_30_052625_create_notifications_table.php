<?php

use Illuminate\Database\Migrations\Migration;
use MongoDB\Laravel\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
              Schema::create('notifications', function (Blueprint $table) {
            $table->index(['notifiable_id', 'notifiable_type']);
            $table->index('read_at');
            // اختياري: حذف الإشعارات تلقائياً بعد 6 أشهر لتوفير المساحة
            $table->index('created_at')->expireAfterSeconds(15552000);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};

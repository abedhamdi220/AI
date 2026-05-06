<?php

use Illuminate\Database\Migrations\Migration;
use MongoDB\Laravel\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * في MongoDB، الهجرة تستخدم أساساً لتعريف الفهارس (Indexes).
     */
    public function up(): void
    {
   Schema::create('users', function (Blueprint $table) {
            $table->unique('username');
            $table->unique('email');
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->index('email');
            // حذف توكن استعادة كلمة المرور تلقائياً بعد ساعة (3600 ثانية) من إنشائه
            $table->index('created_at')->expireAfterSeconds(3600);
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->index('user_id');
            // تنظيف الجلسات غير النشطة تلقائياً
            $table->index('last_activity')->expireAfterSeconds(7200); // ساعتين مثلاً
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};

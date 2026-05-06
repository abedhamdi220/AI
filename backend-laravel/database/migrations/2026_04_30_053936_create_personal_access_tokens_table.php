<?php

use Illuminate\Database\Migrations\Migration;
use MongoDB\Laravel\Schema\Blueprint; // تم التعديل هنا ليتناسب مع MongoDB
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('personal_access_tokens', function (Blueprint $table) {
             $table->index(['tokenable_id', 'tokenable_type']);
            $table->unique('token');

            // TTL Index: سيتم حذف التوكن آلياً من قاعدة البيانات فور انتهاء صلاحيته
            // هذا يوفر مساحة تخزين ويحسن الأداء دون تدخل برمجى
            $table->index('expires_at')->expireAfterSeconds(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('personal_access_tokens');
    }
};

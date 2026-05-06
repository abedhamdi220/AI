<?php

use Illuminate\Database\Migrations\Migration;
use MongoDB\Laravel\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('showcase_designs', function (Blueprint $table) {
            $table->index('design_id');
            $table->index('is_featured');
            $table->index('is_active');
            $table->index('tags'); // MongoDB يدعم فهرسة المصفوفات بشكل ممتاز
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('showcase_designs');
    }
};

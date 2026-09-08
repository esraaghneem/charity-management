<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('volunteers', function (Blueprint $table) {
            $table->id();

            // الاسم بدون تقسيم لغة
            $table->string('name');

            // رقم الهاتف والبريد
            $table->string('phone')->unique();
            $table->string('email')->unique();
            $table->string('password');

            // العنوان باللغتين
            $table->string('address_en');
            $table->string('address_ar');

            // الشهادة الأكاديمية باللغتين
            $table->string('academic_certificate_en')->nullable();
            $table->string('academic_certificate_ar')->nullable();

            // الخبرات باللغتين (nullable نص طويل)
            $table->text('experiences_en')->nullable();
            $table->text('experiences_ar')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('volunteers');
    }
};

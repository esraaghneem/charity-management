<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('beneficiaries', function (Blueprint $table) {
            $table->id();
            // الأسماء الشخصية بدون تقسيم لغة
            $table->string('first_name');
            $table->string('father_name');
            $table->string('mother_name');

            // العنوان باللغتين
            $table->string('address_en')->nullable();
            $table->string('address_ar')->nullable();

            $table->string('phone');
            $table->string('email')->unique();
            $table->string('password');
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('beneficiaries');
    }
};

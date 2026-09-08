<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('achievements', function (Blueprint $table) {
            $table->id();
            $table->string('title_en');       // الإنجاز بالإنجليزية
            $table->string('title_ar');       // الإنجاز بالعربية
            $table->string('location_en');    // الموقع بالإنجليزية
            $table->string('location_ar');    // الموقع بالعربية
            $table->string('image');           // صورة الإنجاز
            $table->text('description_en');   // الوصف بالإنجليزية (يظهر عند الضغط)
            $table->text('description_ar');   // الوصف بالعربية (يظهر عند الضغط)
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('achievements');
    }
};
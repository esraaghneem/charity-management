<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
         
            $table->string('name_en');   // الاسم بالإنجليزي
            $table->string('name_ar');   // الاسم بالعربي

            $table->string('location_en');   // الموقع بالإنجليزي
            $table->string('location_ar');
               // الموقع بالعربي
               $table->boolean('status')->default('1');  // حالة الدور (1 = مفعّل، 0 = غير مفعّل)
               $table->string('image')->nullable();
               $table->string('description_ar');
               $table->string('description_en');
               $table->string('start_end_date');
               $table->string('start_end_time');

            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('roles');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProjectsTable extends Migration
{
    public function up()
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('name_en');   // اسم المشروع بالإنجليزية
            $table->string('name_ar');   // اسم المشروع بالعربية
            $table->string('image')->nullable();  // رابط الصورة (اختياري)
            $table->boolean('status')->default(1);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('projects');
        
    }
}

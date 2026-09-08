<?php
/*
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    
    public function up(): void
    {
        Schema::create('sub_project_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sub_project_id')->constrained()->onDelete('cascade');
            $table->string('key');
            $table->text('value');
            $table->timestamps();
        });
        
    }

   
    public function down(): void
    {
        Schema::dropIfExists('sub_project_details');
    }
};
*/


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('sub_project_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sub_project_id')->constrained()->onDelete('cascade');
            $table->string('key_en');
            $table->string('key_ar');
            $table->text('value_en');
            $table->text('value_ar');
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('sub_project_details');
    }
};

<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('role_volunteer', function (Blueprint $table) {
            $table->id();
            $table->foreignId('volunteer_id')->constrained()->onDelete('cascade');
            $table->foreignId('role_id')->constrained()->onDelete('cascade');
            $table->string('status')->defult('1'); 
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('role_volunteer');
    }
};

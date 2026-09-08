<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    
    public function up(): void
    {
        Schema::create('donations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('donor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sub_project_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('campaign_id')->nullable()->constrained()->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->timestamps();
        });
    }

    
    public function down(): void
    {
        Schema::dropIfExists('donations');
    }
};
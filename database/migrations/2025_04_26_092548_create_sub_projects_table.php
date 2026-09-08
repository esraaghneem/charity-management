<?php


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sub_projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');

            $table->string('name_en');
            $table->string('name_ar');

            $table->string('location_en');
            $table->string('location_ar');

            $table->string('needs_en')->nullable();
            $table->string('needs_ar')->nullable();

            $table->decimal('remaining_amount', 10, 2)->default(0);
            $table->decimal('collected_amount', 10, 2)->default(0);
            $table->decimal('required_amount', 10, 2)->nullable();

            $table->string('image')->nullable();
$table->boolean('status')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sub_projects');
    }
};

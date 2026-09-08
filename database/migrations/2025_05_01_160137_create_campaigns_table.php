<?php
/*

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
   
    public function up() {
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('image')->nullable(); 
            $table->decimal('remaining_amount', 10, 2)->default(0);
            $table->decimal('collected_amount', 10, 2)->default(0);
            $table->decimal('required_amount', 10, 2)->nullable();
            $table->string('location');
            $table->string('status')->default('active');
            $table->dateTime('startDate');
            $table->dateTime('endDate');
          $table->timestamps();
          });
    }
    
    

    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};
*/
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up() {
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();

            $table->string('name_en');
            $table->string('name_ar');

            $table->string('image')->nullable();

            $table->decimal('remaining_amount', 10, 2)->default(0);
            $table->decimal('collected_amount', 10, 2)->default(0);
            $table->decimal('required_amount', 10, 2)->nullable();

            $table->string('location_en');
            $table->string('location_ar');

            $table->boolean('status')->default(1);
            $table->dateTime('startDate');
            $table->dateTime('endDate');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};

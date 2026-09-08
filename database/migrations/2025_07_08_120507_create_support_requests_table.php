<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('support_requests', function (Blueprint $table) {
            $table->id();

            $table->foreignId('beneficiary_id')->constrained('beneficiaries')->onDelete('cascade');

            // الحقول ثنائية اللغة
            $table->string('health_status_en')->nullable();
            $table->string('health_status_ar')->nullable();

            $table->decimal('monthly_income', 10, 2)->nullable();

            $table->string('job_status_en')->nullable();
            $table->string('job_status_ar')->nullable();

            $table->boolean('is_single')->default(false);
            $table->boolean('is_urgent')->default(false);

          

            $table->string('support_type_en')->nullable();
            $table->string('support_type_ar')->nullable();

            $table->boolean('is_male')->default(false);
            $table->boolean('has_family')->default(false);
            $table->boolean('is_male_breadwinner_for_family')->default(false);
            $table->boolean('is_female_breadwinner_for_family')->default(false);
            
            $table->boolean('is_youth_without_family')->default(false);
            $table->boolean('is_girl_without_family')->default(false);
            $table->boolean('is_orphan')->default(false);
            $table->boolean('is_injured')->default(false);
            $table->boolean('is_disabled')->default(false);

            // تفاصيل إضافية
          //  $table->unsignedBigInteger('user_id')->nullable(); // لعلاقة مستقبلية مثلاً
            $table->integer('total_number_of_children')->nullable()->default(0);
            $table->integer('number_of_disabled_children')->nullable()->default(0);

            // الاحتياجات والوصف باللغتين (nullable نص طويل)
            $table->text('needs_en')->nullable();
            $table->text('needs_ar')->nullable();

            $table->string('status')->default('pending');

            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('support_requests');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clinic_appointments', function (Blueprint $table) {
            $table->id();
            $table->string('booking_code', 20)->unique();
            $table->foreignId('service_id')->constrained('clinic_services');
            $table->foreignId('doctor_id')->nullable()->constrained('clinic_doctors')->nullOnDelete();
            $table->foreignId('time_slot_id')->constrained('clinic_time_slots');
            $table->string('patient_name');
            $table->string('phone', 30);
            $table->date('birth_date')->nullable();
            $table->string('gender', 20)->nullable();
            $table->string('address')->nullable();
            $table->string('email')->nullable();
            $table->text('symptoms')->nullable();
            $table->enum('status', ['pending', 'confirmed', 'completed', 'cancelled'])->default('pending');
            $table->text('internal_note')->nullable();
            $table->timestamps();
            $table->index(['phone', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinic_appointments');
    }
};

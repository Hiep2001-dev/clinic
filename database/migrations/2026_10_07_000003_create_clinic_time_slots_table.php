<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clinic_time_slots', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedInteger('max_patients')->default(6);
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();
            $table->unique(['date', 'start_time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinic_time_slots');
    }
};

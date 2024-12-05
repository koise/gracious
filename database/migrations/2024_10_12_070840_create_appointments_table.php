<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->references('id')->on('users')->onDelete('cascade');
            $table->date('appointment_date');
            $table->enum('preference', ['Morning', 'Afternoon'])->default('Morning');
            $table->time('appointment_time');
            $table->enum('status', ['Pending',  'Cancelled', 'Accepted', 'Rejected', 'Missed', 'Ongoing', 'Completed'])->default('Pending');
            $table->string('service');
            $table->string('remarks');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};

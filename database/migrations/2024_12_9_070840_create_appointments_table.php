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
            $table->foreignId('patient_id')->constrained('users')->onDelete('cascade'); // Foreign key to users table
            $table->date('appointment_date'); // Date of the appointment
            $table->enum('preference', ['Morning', 'Afternoon'])->default('Morning'); // Time preference
            $table->time('appointment_time'); // Specific time of the appointment
            $table->enum('status', ['Pending', 'Cancelled', 'Accepted', 'Rejected', 'Missed', 'Ongoing', 'Completed'])->default('Pending'); // Status of the appointment
            $table->string('procedures');
            $table->string('remarks')->nullable(); // Allow remarks to be nullable
            $table->timestamps(); // Created_at and updated_at timestamps
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

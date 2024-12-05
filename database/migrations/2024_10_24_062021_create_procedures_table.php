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
        Schema::create('procedures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_record_id')->references('id')->on('patient_records')->onDelete('cascade');
            $table->foreignId('appointment_id')->references('id')->on('appointments')->onDelete('cascade');
            $table->string('procedure');
            $table->integer('amount')->unsigned();
            $table->integer('paid')->unsigned();
            $table->integer('balance')->unsigned();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('procedures');
    }
};

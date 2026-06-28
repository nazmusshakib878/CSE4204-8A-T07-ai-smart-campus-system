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
        Schema::create('risk_alerts', function (Blueprint $table) {
            $table->id();
            
            // Foreign Key
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            
            // Columns
            $table->enum('risk_level', ['low', 'medium', 'high'])->default('low');
            $table->string('prediction', 255);
            $table->text('advice')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('risk_alerts');
    }
};
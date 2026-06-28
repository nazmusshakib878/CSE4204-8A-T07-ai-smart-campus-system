<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessment_records', function (Blueprint $table) {
            $table->id();
            // Foreign Keys
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            
            // Assessment Marks
            $table->float('quiz_marks')->default(0);
            $table->float('assignment_marks')->default(0);
            $table->float('mid_marks')->default(0);
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_records');
    }
};
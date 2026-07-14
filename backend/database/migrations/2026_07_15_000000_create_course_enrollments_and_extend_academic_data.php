<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->string('department')->nullable()->after('title');
            $table->decimal('credit_hours', 4, 1)->default(3)->after('department');
            $table->boolean('is_active')->default(true)->after('description');
        });

        Schema::create('course_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->string('semester', 50);
            $table->unsignedSmallInteger('year');
            $table->timestamps();
            $table->unique(['course_id', 'student_id', 'semester', 'year'], 'course_student_term_unique');
        });

        Schema::table('performance_metrics', function (Blueprint $table) {
            $table->string('semester', 50)->nullable()->after('student_id');
            $table->unsignedSmallInteger('year')->nullable()->after('semester');
        });
    }

    public function down(): void
    {
        Schema::table('performance_metrics', function (Blueprint $table) {
            $table->dropColumn(['semester', 'year']);
        });
        Schema::dropIfExists('course_enrollments');
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn(['department', 'credit_hours', 'is_active']);
        });
    }
};

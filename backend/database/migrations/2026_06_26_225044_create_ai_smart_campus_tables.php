<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['student', 'faculty', 'admin'])->default('student')->after('password');
            $table->enum('approval_status', ['pending', 'approved', 'rejected'])->default('pending')->after('role');
        });

        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->unsignedBigInteger('department_id')->nullable();
            $table->string('program', 100);
            $table->integer('admission_year');
            $table->timestamps();
        });

        Schema::create('faculty', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('department', 100);
            $table->string('designation', 100);
            $table->timestamps();
        });

        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('course_code', 50)->unique();
            $table->string('course_name', 255);
            $table->float('credits');
            $table->timestamps();
        });

        Schema::create('academic_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->string('semester', 50);
            $table->string('grade', 5)->nullable();
            $table->float('marks')->default(0);
            $table->timestamps();
        });

        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->float('attendance_percentage')->default(0);
            $table->string('semester', 50);
            $table->timestamps();
        });

        Schema::create('assessment_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->float('quiz_marks')->default(0);
            $table->float('assignment_marks')->default(0);
            $table->float('mid_marks')->default(0);
            $table->timestamps();
        });

        Schema::create('performance_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->float('cgpa')->default(0);
            $table->float('credit_completed')->default(0);
            $table->float('attendance_percentage')->default(0);
            $table->float('quiz_avg')->default(0);
            $table->timestamps();
        });

        Schema::create('risk_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->enum('risk_level', ['low', 'medium', 'high'])->default('low');
            $table->string('prediction', 255);
            $table->text('advice')->nullable();
            $table->timestamps();
        });

        Schema::create('course_recommendations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('recommended_course_id')->constrained('courses')->cascadeOnDelete();
            $table->string('target_semester', 50);
            $table->text('study_path_plan')->nullable();
            $table->timestamps();
        });

        Schema::create('notices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title', 255);
            $table->text('description');
            $table->timestamp('publish_date')->nullable();
            $table->timestamps();
        });

        Schema::create('ai_chat_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('query');
            $table->text('ai_response');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_chat_logs');
        Schema::dropIfExists('notices');
        Schema::dropIfExists('course_recommendations');
        Schema::dropIfExists('risk_alerts');
        Schema::dropIfExists('performance_metrics');
        Schema::dropIfExists('assessment_records');
        Schema::dropIfExists('attendance_records');
        Schema::dropIfExists('academic_records');
        Schema::dropIfExists('courses');
        Schema::dropIfExists('faculty');
        Schema::dropIfExists('students');
        Schema::dropIfExists('admins');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'approval_status']);
        });
    }
};
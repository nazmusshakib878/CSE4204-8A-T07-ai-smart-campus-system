<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('section', 20)->nullable()->after('current_semester');
        });

        Schema::table('academic_records', function (Blueprint $table) {
            $table->string('course_code_snapshot', 30)->nullable()->after('course_id');
            $table->string('course_title_snapshot')->nullable()->after('course_code_snapshot');
            $table->decimal('credit_hours_snapshot', 5, 2)->nullable()->after('course_title_snapshot');
            $table->decimal('grade_point', 4, 2)->nullable()->after('grade');
            $table->unsignedTinyInteger('semester_number')->nullable()->after('semester');
            $table->string('source', 50)->nullable()->after('year');
            $table->unique(['student_id', 'course_code_snapshot', 'semester_number'], 'academic_record_student_snapshot_unique');
        });
    }

    public function down(): void
    {
        Schema::table('academic_records', function (Blueprint $table) {
            $table->dropUnique('academic_record_student_snapshot_unique');
            $table->dropColumn(['course_code_snapshot', 'course_title_snapshot', 'credit_hours_snapshot', 'grade_point', 'semester_number', 'source']);
        });

        Schema::table('students', fn (Blueprint $table) => $table->dropColumn('section'));
    }
};
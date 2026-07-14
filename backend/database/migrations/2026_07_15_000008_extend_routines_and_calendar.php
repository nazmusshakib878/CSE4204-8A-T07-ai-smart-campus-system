<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('course_schedules', function (Blueprint $table) {
            $table->index('course_id', 'course_schedules_course_id_index');
            $table->dropUnique('course_schedules_course_id_day_of_week_starts_at_unique');
            $table->string('section', 50)->nullable()->after('year');
            $table->unique(['course_id', 'semester', 'year', 'section', 'day_of_week', 'starts_at'], 'course_schedule_term_slot_unique');
        });
        Schema::table('exam_routines', function (Blueprint $table) {
            $table->string('section', 50)->nullable()->after('year');
        });
        Schema::table('academic_events', function (Blueprint $table) {
            $table->boolean('is_all_day')->default(true)->after('audience');
            $table->string('recurrence', 20)->default('none')->after('is_all_day');
        });
    }

    public function down(): void
    {
        Schema::table('academic_events', fn (Blueprint $table) => $table->dropColumn(['is_all_day', 'recurrence']));
        Schema::table('exam_routines', fn (Blueprint $table) => $table->dropColumn('section'));
        Schema::table('course_schedules', function (Blueprint $table) { $table->dropUnique('course_schedule_term_slot_unique'); $table->dropColumn('section'); $table->unique(['course_id', 'day_of_week', 'starts_at']); });
    }
};

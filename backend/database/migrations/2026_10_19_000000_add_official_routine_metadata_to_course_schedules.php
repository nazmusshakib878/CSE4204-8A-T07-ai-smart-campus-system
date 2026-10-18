<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('course_schedules', function (Blueprint $table): void {
            $table->string('shift', 20)->nullable()->after('section');
            $table->string('instructor_initials', 20)->nullable()->after('room');
            $table->string('source_reference', 255)->nullable()->after('class_type');
        });
    }

    public function down(): void
    {
        Schema::table('course_schedules', function (Blueprint $table): void {
            $table->dropColumn(['shift', 'instructor_initials', 'source_reference']);
        });
    }
};

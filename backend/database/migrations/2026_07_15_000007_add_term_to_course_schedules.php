<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void { Schema::table('course_schedules', function (Blueprint $table) { $table->string('semester', 20)->nullable()->after('course_id'); $table->unsignedSmallInteger('year')->nullable()->after('semester'); }); }
    public function down(): void { Schema::table('course_schedules', fn (Blueprint $table) => $table->dropColumn(['semester', 'year'])); }
};

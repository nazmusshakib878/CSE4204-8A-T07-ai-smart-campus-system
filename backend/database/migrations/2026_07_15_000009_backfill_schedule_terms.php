<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void { DB::table('course_schedules')->whereNull('semester')->update(['semester' => 'Spring']); DB::table('course_schedules')->whereNull('year')->update(['year' => (int) date('Y')]); }
    public function down(): void {}
};

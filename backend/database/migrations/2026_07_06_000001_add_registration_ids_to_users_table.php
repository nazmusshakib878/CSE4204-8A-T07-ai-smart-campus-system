<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'department')) {
                $table->string('department')->nullable()->after('role');
            }

            if (! Schema::hasColumn('users', 'student_id')) {
                $table->string('student_id')->nullable()->unique()->after('department');
            }

            if (! Schema::hasColumn('users', 'faculty_id')) {
                $table->string('faculty_id')->nullable()->unique()->after('student_id');
            }

            if (! Schema::hasColumn('users', 'admin_id')) {
                $table->string('admin_id')->nullable()->unique()->after('faculty_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'admin_id')) {
                $table->dropUnique(['admin_id']);
                $table->dropColumn('admin_id');
            }

            if (Schema::hasColumn('users', 'faculty_id')) {
                $table->dropUnique(['faculty_id']);
                $table->dropColumn('faculty_id');
            }

            if (Schema::hasColumn('users', 'student_id')) {
                $table->dropUnique(['student_id']);
                $table->dropColumn('student_id');
            }

            if (Schema::hasColumn('users', 'department')) {
                $table->dropColumn('department');
            }
        });
    }
};
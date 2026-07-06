<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notices', function (Blueprint $table) {
            if (! Schema::hasColumn('notices', 'target_department')) {
                $table->string('target_department')->nullable()->after('audience');
            }

            if (! Schema::hasColumn('notices', 'target_role')) {
                $table->string('target_role')->nullable()->after('target_department');
            }

            if (! Schema::hasColumn('notices', 'target_semester')) {
                $table->string('target_semester')->nullable()->after('target_role');
            }
        });
    }

    public function down(): void
    {
        Schema::table('notices', function (Blueprint $table) {
            $table->dropColumn(['target_department', 'target_role', 'target_semester']);
        });
    }
};
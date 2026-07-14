<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('risk_alerts', function (Blueprint $table) {
            $table->foreignId('faculty_user_id')->nullable()->after('student_id')->constrained('users')->nullOnDelete();
            $table->unsignedTinyInteger('risk_score')->nullable()->after('risk_level');
            $table->json('reasons')->nullable()->after('advice');
            $table->string('source', 30)->default('baseline')->after('reasons');
            $table->string('model', 100)->nullable()->after('source');
            $table->timestamp('analyzed_at')->nullable()->after('model');
        });
    }

    public function down(): void
    {
        Schema::table('risk_alerts', function (Blueprint $table) {
            $table->dropForeign(['faculty_user_id']);
            $table->dropColumn([
                'faculty_user_id',
                'risk_score',
                'reasons',
                'source',
                'model',
                'analyzed_at',
            ]);
        });
    }
};

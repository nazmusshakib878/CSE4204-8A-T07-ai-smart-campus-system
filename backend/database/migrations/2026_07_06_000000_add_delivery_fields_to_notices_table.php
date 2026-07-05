<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notices', function (Blueprint $table) {
            $table->string('category')->default('Academic')->after('publish_date');
            $table->string('audience')->default('All')->after('category');
            $table->string('recipient_name')->nullable()->after('audience');
            $table->string('recipient_reference')->nullable()->after('recipient_name');
        });
    }

    public function down(): void
    {
        Schema::table('notices', function (Blueprint $table) {
            $table->dropColumn([
                'category',
                'audience',
                'recipient_name',
                'recipient_reference',
            ]);
        });
    }
};

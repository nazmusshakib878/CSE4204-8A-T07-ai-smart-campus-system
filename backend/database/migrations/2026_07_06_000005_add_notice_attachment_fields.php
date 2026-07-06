<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notices', function (Blueprint $table) {
            if (! Schema::hasColumn('notices', 'attachment_path')) {
                $table->string('attachment_path')->nullable()->after('recipient_reference');
            }

            if (! Schema::hasColumn('notices', 'attachment_name')) {
                $table->string('attachment_name')->nullable()->after('attachment_path');
            }

            if (! Schema::hasColumn('notices', 'attachment_mime')) {
                $table->string('attachment_mime')->nullable()->after('attachment_name');
            }

            if (! Schema::hasColumn('notices', 'attachment_size')) {
                $table->unsignedBigInteger('attachment_size')->nullable()->after('attachment_mime');
            }
        });
    }

    public function down(): void
    {
        Schema::table('notices', function (Blueprint $table) {
            $table->dropColumn(['attachment_path', 'attachment_name', 'attachment_mime', 'attachment_size']);
        });
    }
};
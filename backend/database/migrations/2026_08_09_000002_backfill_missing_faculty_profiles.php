<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('faculty')) {
            return;
        }

        DB::table('users')
            ->where('role', 'faculty')
            ->orderBy('id')
            ->each(function ($user): void {
                DB::table('faculty')->updateOrInsert(
                    ['user_id' => $user->id],
                    [
                        'department' => $user->department,
                        'designation' => 'Faculty Member',
                        'created_at' => $user->created_at ?? now(),
                        'updated_at' => now(),
                    ],
                );
            });
    }

    public function down(): void
    {
        // Role profiles may own course relations, so repaired links are intentionally retained.
    }
};

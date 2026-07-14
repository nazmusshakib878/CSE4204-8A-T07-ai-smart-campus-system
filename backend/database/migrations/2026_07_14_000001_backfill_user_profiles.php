<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('students')) {
            DB::table('users')->where('role', 'student')->orderBy('id')->each(function ($user) {
                DB::table('students')->updateOrInsert(
                    ['user_id' => $user->id],
                    [
                        'student_number' => $user->student_id,
                        'department' => $user->department,
                        'program' => $user->department,
                        'created_at' => $user->created_at ?? now(),
                        'updated_at' => now(),
                    ]
                );
            });
        }

        if (Schema::hasTable('faculty')) {
            DB::table('users')->where('role', 'faculty')->orderBy('id')->each(function ($user) {
                DB::table('faculty')->updateOrInsert(
                    ['user_id' => $user->id],
                    [
                        'department' => $user->department,
                        'designation' => 'Faculty Member',
                        'created_at' => $user->created_at ?? now(),
                        'updated_at' => now(),
                    ]
                );
            });
        }
    }

    public function down(): void
    {
        // Profiles are intentionally retained because they may contain academic relations.
    }
};

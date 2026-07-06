<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('code', 10)->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        DB::table('departments')->insert([
            ['name' => 'Computer Science & Engineering', 'code' => 'CSE', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Electrical & Electronic Engineering', 'code' => 'EEE', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Mechanical Engineering', 'code' => 'ME', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Civil Engineering', 'code' => 'CE', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Chemical Engineering', 'code' => 'CHE', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('departments');
    }
};
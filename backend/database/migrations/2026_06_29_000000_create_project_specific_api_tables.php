<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('learning_resources')) {
            Schema::create('learning_resources', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->text('description')->nullable();
                $table->string('category');
                $table->string('resource_type');
                $table->string('resource_url')->nullable();
                $table->string('uploaded_by')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('tasks')) {
            Schema::create('tasks', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->text('description')->nullable();
                $table->string('assigned_to')->nullable();
                $table->date('due_date')->nullable();
                $table->enum('status', ['pending', 'in_progress', 'completed'])->default('pending');
                $table->enum('priority', ['low', 'medium', 'high'])->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('recommendations')) {
            Schema::create('recommendations', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->text('description')->nullable();
                $table->string('recommendation_type');
                $table->string('target_user')->nullable();
                $table->float('score')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('recommendations');
        Schema::dropIfExists('tasks');
        Schema::dropIfExists('learning_resources');
    }
};

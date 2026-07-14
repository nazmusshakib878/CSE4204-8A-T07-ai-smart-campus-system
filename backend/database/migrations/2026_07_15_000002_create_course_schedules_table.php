<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void { Schema::create('course_schedules',function(Blueprint $table){ $table->id(); $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete(); $table->unsignedTinyInteger('day_of_week'); $table->time('starts_at'); $table->time('ends_at'); $table->string('room')->nullable(); $table->enum('class_type',['lecture','lab'])->default('lecture'); $table->timestamps(); $table->unique(['course_id','day_of_week','starts_at']); }); }
 public function down(): void { Schema::dropIfExists('course_schedules'); }
};

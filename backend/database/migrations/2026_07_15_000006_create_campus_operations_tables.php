<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assessment_records', function (Blueprint $table) {
            $table->float('final_marks')->default(0)->after('mid_marks');
            $table->float('total_marks')->default(0)->after('final_marks');
            $table->string('semester', 20)->nullable()->after('course_id');
            $table->unsignedSmallInteger('year')->nullable()->after('semester');
            $table->unique(['student_id', 'course_id', 'semester', 'year'], 'assessment_student_course_term_unique');
        });

        Schema::create('exam_routines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->restrictOnDelete();
            $table->string('semester', 20);
            $table->unsignedSmallInteger('year');
            $table->string('exam_type', 40);
            $table->date('exam_date');
            $table->time('starts_at');
            $table->time('ends_at');
            $table->string('room')->nullable();
            $table->timestamps();
            $table->unique(['course_id', 'semester', 'year', 'exam_type']);
        });

        Schema::create('academic_events', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('starts_on');
            $table->date('ends_on')->nullable();
            $table->string('event_type', 40)->default('academic');
            $table->string('audience', 30)->default('all');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });

        Schema::create('fee_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->restrictOnDelete();
            $table->string('semester', 20);
            $table->unsignedSmallInteger('year');
            $table->decimal('amount_due', 12, 2);
            $table->decimal('amount_paid', 12, 2)->default(0);
            $table->date('due_date')->nullable();
            $table->string('status', 20)->default('unpaid');
            $table->string('reference')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['student_id', 'semester', 'year']);
        });

        Schema::create('faculty_leaves', function (Blueprint $table) {
            $table->id();
            $table->foreignId('faculty_id')->constrained('faculty')->restrictOnDelete();
            $table->date('starts_on');
            $table->date('ends_on');
            $table->text('reason');
            $table->string('status', 20)->default('pending');
            $table->text('admin_note')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('class_reschedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->restrictOnDelete();
            $table->foreignId('faculty_id')->constrained('faculty')->restrictOnDelete();
            $table->date('original_date');
            $table->date('new_date');
            $table->time('starts_at');
            $table->time('ends_at');
            $table->string('room')->nullable();
            $table->string('status', 20)->default('pending');
            $table->text('reason')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('library_books', function (Blueprint $table) {
            $table->id();
            $table->string('isbn')->nullable()->unique();
            $table->string('title');
            $table->string('author');
            $table->string('category')->nullable();
            $table->unsignedInteger('total_copies')->default(1);
            $table->unsignedInteger('available_copies')->default(1);
            $table->string('shelf')->nullable();
            $table->timestamps();
        });

        Schema::create('library_loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained('library_books')->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->date('borrowed_on');
            $table->date('due_on');
            $table->date('returned_on')->nullable();
            $table->string('status', 20)->default('borrowed');
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('helpdesk_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('category', 50);
            $table->string('subject');
            $table->text('description');
            $table->string('priority', 20)->default('medium');
            $table->string('status', 20)->default('open');
            $table->text('response')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('helpdesk_tickets');
        Schema::dropIfExists('library_loans');
        Schema::dropIfExists('library_books');
        Schema::dropIfExists('class_reschedules');
        Schema::dropIfExists('faculty_leaves');
        Schema::dropIfExists('fee_records');
        Schema::dropIfExists('academic_events');
        Schema::dropIfExists('exam_routines');
        Schema::table('assessment_records', function (Blueprint $table) {
            $table->dropUnique('assessment_student_course_term_unique');
            $table->dropColumn(['final_marks', 'total_marks', 'semester', 'year']);
        });
    }
};

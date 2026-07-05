<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('students')) {
            Schema::table('students', function (Blueprint $table) {
                if (! Schema::hasColumn('students', 'user_id')) {
                    $table->foreignId('user_id')->constrained('users')->cascadeOnDelete()->after('id');
                }
                if (! Schema::hasColumn('students', 'student_number')) {
                    $table->string('student_number')->nullable()->unique()->after('user_id');
                }
                if (! Schema::hasColumn('students', 'department')) {
                    $table->string('department')->nullable()->after('student_number');
                }
                if (! Schema::hasColumn('students', 'program')) {
                    $table->string('program')->nullable()->after('department');
                }
            });
        }

        if (Schema::hasTable('faculty')) {
            Schema::table('faculty', function (Blueprint $table) {
                if (! Schema::hasColumn('faculty', 'user_id')) {
                    $table->foreignId('user_id')->constrained('users')->cascadeOnDelete()->after('id');
                }
                if (! Schema::hasColumn('faculty', 'department')) {
                    $table->string('department')->nullable()->after('user_id');
                }
                if (! Schema::hasColumn('faculty', 'designation')) {
                    $table->string('designation')->nullable()->after('department');
                }
            });
        }

        if (Schema::hasTable('courses')) {
            Schema::table('courses', function (Blueprint $table) {
                if (! Schema::hasColumn('courses', 'faculty_id')) {
                    $table->foreignId('faculty_id')->nullable()->constrained('faculty')->nullOnDelete()->after('id');
                }
                if (! Schema::hasColumn('courses', 'course_code')) {
                    $table->string('course_code')->nullable()->unique()->after('faculty_id');
                }
                if (! Schema::hasColumn('courses', 'title')) {
                    $table->string('title')->nullable()->after('course_code');
                }
                if (! Schema::hasColumn('courses', 'description')) {
                    $table->text('description')->nullable()->after('title');
                }
            });
        }

        if (Schema::hasTable('academic_records')) {
            Schema::table('academic_records', function (Blueprint $table) {
                if (! Schema::hasColumn('academic_records', 'student_id')) {
                    $table->foreignId('student_id')->nullable()->constrained('students')->nullOnDelete()->after('id');
                }
                if (! Schema::hasColumn('academic_records', 'course_id')) {
                    $table->foreignId('course_id')->nullable()->constrained('courses')->nullOnDelete()->after('student_id');
                }
                if (! Schema::hasColumn('academic_records', 'grade')) {
                    $table->string('grade', 20)->nullable()->after('course_id');
                }
                if (! Schema::hasColumn('academic_records', 'semester')) {
                    $table->string('semester', 50)->nullable()->after('grade');
                }
                if (! Schema::hasColumn('academic_records', 'year')) {
                    $table->integer('year')->nullable()->after('semester');
                }
            });
        }

        if (Schema::hasTable('attendance_records')) {
            Schema::table('attendance_records', function (Blueprint $table) {
                if (! Schema::hasColumn('attendance_records', 'student_id')) {
                    $table->foreignId('student_id')->nullable()->constrained('students')->nullOnDelete()->after('id');
                }
                if (! Schema::hasColumn('attendance_records', 'course_id')) {
                    $table->foreignId('course_id')->nullable()->constrained('courses')->nullOnDelete()->after('student_id');
                }
                if (! Schema::hasColumn('attendance_records', 'attendance_date')) {
                    $table->date('attendance_date')->nullable()->after('course_id');
                }
                if (! Schema::hasColumn('attendance_records', 'status')) {
                    $table->enum('status', ['present', 'absent', 'late'])->default('present')->after('attendance_date');
                }
            });
        }

        if (Schema::hasTable('performance_metrics')) {
            Schema::table('performance_metrics', function (Blueprint $table) {
                if (! Schema::hasColumn('performance_metrics', 'student_id')) {
                    $table->foreignId('student_id')->nullable()->constrained('students')->nullOnDelete()->after('id');
                }
                if (! Schema::hasColumn('performance_metrics', 'cgpa')) {
                    $table->float('cgpa')->nullable()->after('student_id');
                }
                if (! Schema::hasColumn('performance_metrics', 'semester_gpa')) {
                    $table->float('semester_gpa')->nullable()->after('cgpa');
                }
                if (! Schema::hasColumn('performance_metrics', 'completed_credits')) {
                    $table->integer('completed_credits')->nullable()->after('semester_gpa');
                }
            });
        }

        if (Schema::hasTable('course_recommendations')) {
            Schema::table('course_recommendations', function (Blueprint $table) {
                if (! Schema::hasColumn('course_recommendations', 'student_id')) {
                    $table->foreignId('student_id')->nullable()->constrained('students')->nullOnDelete()->after('id');
                }
                if (! Schema::hasColumn('course_recommendations', 'course_id')) {
                    $table->foreignId('course_id')->nullable()->constrained('courses')->nullOnDelete()->after('student_id');
                }
                if (! Schema::hasColumn('course_recommendations', 'reason')) {
                    $table->text('reason')->nullable()->after('course_id');
                }
                if (! Schema::hasColumn('course_recommendations', 'score')) {
                    $table->float('score')->nullable()->after('reason');
                }
            });
        }

        if (Schema::hasTable('recommendations')) {
            Schema::table('recommendations', function (Blueprint $table) {
                if (! Schema::hasColumn('recommendations', 'target_user_id')) {
                    $table->foreignId('target_user_id')->nullable()->constrained('users')->nullOnDelete()->after('recommendation_type');
                }
                if (! Schema::hasColumn('recommendations', 'course_id')) {
                    $table->foreignId('course_id')->nullable()->constrained('courses')->nullOnDelete()->after('target_user_id');
                }
                if (! Schema::hasColumn('recommendations', 'score')) {
                    $table->float('score')->nullable()->after('course_id');
                }
            });
        }

        if (Schema::hasTable('learning_resources')) {
            Schema::table('learning_resources', function (Blueprint $table) {
                if (! Schema::hasColumn('learning_resources', 'course_id')) {
                    $table->foreignId('course_id')->nullable()->constrained('courses')->nullOnDelete()->after('resource_url');
                }
                if (! Schema::hasColumn('learning_resources', 'uploaded_by_user_id')) {
                    $table->foreignId('uploaded_by_user_id')->nullable()->constrained('users')->nullOnDelete()->after('course_id');
                }
            });
        }

        if (Schema::hasTable('tasks')) {
            Schema::table('tasks', function (Blueprint $table) {
                if (! Schema::hasColumn('tasks', 'assigned_to_user_id')) {
                    $table->foreignId('assigned_to_user_id')->nullable()->constrained('users')->nullOnDelete()->after('assigned_to');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('tasks')) {
            Schema::table('tasks', function (Blueprint $table) {
                if (Schema::hasColumn('tasks', 'assigned_to_user_id')) {
                    $table->dropForeign(['assigned_to_user_id']);
                    $table->dropColumn('assigned_to_user_id');
                }
            });
        }

        if (Schema::hasTable('learning_resources')) {
            Schema::table('learning_resources', function (Blueprint $table) {
                if (Schema::hasColumn('learning_resources', 'uploaded_by_user_id')) {
                    $table->dropForeign(['uploaded_by_user_id']);
                    $table->dropColumn('uploaded_by_user_id');
                }
                if (Schema::hasColumn('learning_resources', 'course_id')) {
                    $table->dropForeign(['course_id']);
                    $table->dropColumn('course_id');
                }
            });
        }

        if (Schema::hasTable('recommendations')) {
            Schema::table('recommendations', function (Blueprint $table) {
                if (Schema::hasColumn('recommendations', 'course_id')) {
                    $table->dropForeign(['course_id']);
                    $table->dropColumn('course_id');
                }
                if (Schema::hasColumn('recommendations', 'target_user_id')) {
                    $table->dropForeign(['target_user_id']);
                    $table->dropColumn('target_user_id');
                }
                if (Schema::hasColumn('recommendations', 'score')) {
                    $table->dropColumn('score');
                }
            });
        }

        if (Schema::hasTable('course_recommendations')) {
            Schema::table('course_recommendations', function (Blueprint $table) {
                if (Schema::hasColumn('course_recommendations', 'score')) {
                    $table->dropColumn('score');
                }
                if (Schema::hasColumn('course_recommendations', 'reason')) {
                    $table->dropColumn('reason');
                }
                if (Schema::hasColumn('course_recommendations', 'course_id')) {
                    $table->dropForeign(['course_id']);
                    $table->dropColumn('course_id');
                }
                if (Schema::hasColumn('course_recommendations', 'student_id')) {
                    $table->dropForeign(['student_id']);
                    $table->dropColumn('student_id');
                }
            });
        }

        if (Schema::hasTable('performance_metrics')) {
            Schema::table('performance_metrics', function (Blueprint $table) {
                if (Schema::hasColumn('performance_metrics', 'completed_credits')) {
                    $table->dropColumn('completed_credits');
                }
                if (Schema::hasColumn('performance_metrics', 'semester_gpa')) {
                    $table->dropColumn('semester_gpa');
                }
                if (Schema::hasColumn('performance_metrics', 'cgpa')) {
                    $table->dropColumn('cgpa');
                }
                if (Schema::hasColumn('performance_metrics', 'student_id')) {
                    $table->dropForeign(['student_id']);
                    $table->dropColumn('student_id');
                }
            });
        }

        if (Schema::hasTable('attendance_records')) {
            Schema::table('attendance_records', function (Blueprint $table) {
                if (Schema::hasColumn('attendance_records', 'status')) {
                    $table->dropColumn('status');
                }
                if (Schema::hasColumn('attendance_records', 'attendance_date')) {
                    $table->dropColumn('attendance_date');
                }
                if (Schema::hasColumn('attendance_records', 'course_id')) {
                    $table->dropForeign(['course_id']);
                    $table->dropColumn('course_id');
                }
                if (Schema::hasColumn('attendance_records', 'student_id')) {
                    $table->dropForeign(['student_id']);
                    $table->dropColumn('student_id');
                }
            });
        }

        if (Schema::hasTable('academic_records')) {
            Schema::table('academic_records', function (Blueprint $table) {
                if (Schema::hasColumn('academic_records', 'year')) {
                    $table->dropColumn('year');
                }
                if (Schema::hasColumn('academic_records', 'semester')) {
                    $table->dropColumn('semester');
                }
                if (Schema::hasColumn('academic_records', 'grade')) {
                    $table->dropColumn('grade');
                }
                if (Schema::hasColumn('academic_records', 'course_id')) {
                    $table->dropForeign(['course_id']);
                    $table->dropColumn('course_id');
                }
                if (Schema::hasColumn('academic_records', 'student_id')) {
                    $table->dropForeign(['student_id']);
                    $table->dropColumn('student_id');
                }
            });
        }

        if (Schema::hasTable('courses')) {
            Schema::table('courses', function (Blueprint $table) {
                if (Schema::hasColumn('courses', 'description')) {
                    $table->dropColumn('description');
                }
                if (Schema::hasColumn('courses', 'title')) {
                    $table->dropColumn('title');
                }
                if (Schema::hasColumn('courses', 'course_code')) {
                    $table->dropColumn('course_code');
                }
                if (Schema::hasColumn('courses', 'faculty_id')) {
                    $table->dropForeign(['faculty_id']);
                    $table->dropColumn('faculty_id');
                }
            });
        }

        if (Schema::hasTable('faculty')) {
            Schema::table('faculty', function (Blueprint $table) {
                if (Schema::hasColumn('faculty', 'designation')) {
                    $table->dropColumn('designation');
                }
                if (Schema::hasColumn('faculty', 'department')) {
                    $table->dropColumn('department');
                }
                if (Schema::hasColumn('faculty', 'user_id')) {
                    $table->dropForeign(['user_id']);
                    $table->dropColumn('user_id');
                }
            });
        }

        if (Schema::hasTable('students')) {
            Schema::table('students', function (Blueprint $table) {
                if (Schema::hasColumn('students', 'program')) {
                    $table->dropColumn('program');
                }
                if (Schema::hasColumn('students', 'department')) {
                    $table->dropColumn('department');
                }
                if (Schema::hasColumn('students', 'student_number')) {
                    $table->dropColumn('student_number');
                }
                if (Schema::hasColumn('students', 'user_id')) {
                    $table->dropForeign(['user_id']);
                    $table->dropColumn('user_id');
                }
            });
        }
    }
};
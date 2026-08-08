<?php

namespace Tests\Feature;

use App\Models\AcademicRecord;
use App\Models\PerformanceMetric;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TranscriptSnapshotTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_dashboard_and_export_display_historical_snapshot_without_catalog_course(): void
    {
        $user = User::factory()->create(['role' => 'student', 'student_id' => 'CSE220320852']);
        $student = Student::create([
            'user_id' => $user->id,
            'student_number' => 'CSE220320852',
            'department' => 'Computer Science & Engineering',
            'program' => 'BSc in CSE',
            'current_semester' => 8,
            'section' => '8A',
        ]);
        AcademicRecord::create([
            'student_id' => $student->id,
            'course_code_snapshot' => 'CSE 4101',
            'course_title_snapshot' => 'Compiler Design',
            'credit_hours_snapshot' => 3,
            'grade' => 'A+',
            'grade_point' => 4,
            'semester' => 'Semester 7',
            'semester_number' => 7,
            'year' => 2025,
            'source' => 'NUB overall-result PDF',
        ]);
        PerformanceMetric::create([
            'student_id' => $student->id,
            'semester' => 'Semester 7',
            'year' => 2025,
            'cgpa' => 3.605,
            'semester_gpa' => 4,
            'completed_credits' => 129,
        ]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/student/dashboard')
            ->assertOk()
            ->assertJsonPath('data.summary.section', '8A')
            ->assertJsonPath('data.summary.current_semester_number', 8)
            ->assertJsonPath('data.gradebook.0.course_code', 'CSE 4101')
            ->assertJsonPath('data.gradebook.0.course_title', 'Compiler Design')
            ->assertJsonPath('data.gradebook.0.credits', 3);

        $this->actingAs($user, 'sanctum')
            ->get('/api/student/transcript')
            ->assertOk()
            ->assertSee('CSE220320852')
            ->assertSee('Compiler Design')
            ->assertSee('8A');
    }
}
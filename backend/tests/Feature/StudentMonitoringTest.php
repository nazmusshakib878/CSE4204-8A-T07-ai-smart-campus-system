<?php

namespace Tests\Feature;

use App\Models\AcademicRecord;
use App\Models\AttendanceRecord;
use App\Models\Course;
use App\Models\Faculty;
use App\Models\PerformanceMetric;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class StudentMonitoringTest extends TestCase
{
    use RefreshDatabase;

    public function test_faculty_only_sees_students_from_assigned_courses(): void
    {
        [$facultyUser, $student, $course] = $this->createAssignedStudent();

        $otherUser = User::factory()->create([
            'role' => 'student',
            'department' => 'Electrical & Electronic Engineering',
            'student_id' => 'EEE11220320852',
        ]);
        Student::create([
            'user_id' => $otherUser->id,
            'student_number' => $otherUser->student_id,
            'department' => $otherUser->department,
        ]);

        $response = $this->actingAs($facultyUser, 'sanctum')
            ->getJson('/api/faculty/student-monitoring');

        $response
            ->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.students.0.database_id', $student->id)
            ->assertJsonPath('data.students.0.attendance', 75)
            ->assertJsonPath('data.students.0.cgpa', 2.8)
            ->assertJsonPath('data.students.0.courses.0', $course->course_code)
            ->assertJsonCount(1, 'data.students')
            ->assertJsonStructure([
                'data' => [
                    'students',
                    'charts' => ['performance_trend', 'score_distribution'],
                    'summary',
                    'ai_configured',
                ],
            ]);
    }

    public function test_student_cannot_access_faculty_monitoring(): void
    {
        $studentUser = User::factory()->create(['role' => 'student']);

        $this->actingAs($studentUser, 'sanctum')
            ->getJson('/api/faculty/student-monitoring')
            ->assertForbidden()
            ->assertJsonPath('status', false);
    }

    public function test_openai_analysis_is_structured_and_persisted(): void
    {
        [$facultyUser, $student] = $this->createAssignedStudent();
        config([
            'services.openai.api_key' => 'test-key',
            'services.openai.model' => 'gpt-4.1-mini',
        ]);

        Http::fake([
            'api.openai.com/v1/responses' => Http::response([
                'output_text' => json_encode([
                    'risk_score' => 72,
                    'risk_level' => 'high',
                    'prediction' => 'The student is likely to need academic support.',
                    'reasons' => ['Attendance is below target', 'CGPA trend needs attention'],
                    'advice' => 'Schedule an advising meeting and provide focused learning resources.',
                ]),
            ]),
        ]);

        $this->actingAs($facultyUser, 'sanctum')
            ->postJson("/api/faculty/students/{$student->id}/analyze-risk")
            ->assertOk()
            ->assertJsonPath('data.risk_score', 72)
            ->assertJsonPath('data.risk_level', 'high')
            ->assertJsonPath('data.source', 'openai')
            ->assertJsonPath('data.model', 'gpt-4.1-mini');

        $this->assertDatabaseHas('risk_alerts', [
            'student_id' => $student->id,
            'faculty_user_id' => $facultyUser->id,
            'risk_level' => 'high',
            'risk_score' => 72,
            'source' => 'openai',
            'model' => 'gpt-4.1-mini',
        ]);

        Http::assertSent(fn ($request) => (
            $request->url() === 'https://api.openai.com/v1/responses'
            && $request['text']['format']['type'] === 'json_schema'
            && str_contains($request['input'], $student->student_number)
        ));
    }

    public function test_ai_endpoint_reports_missing_configuration(): void
    {
        [$facultyUser, $student] = $this->createAssignedStudent();
        config(['services.openai.api_key' => null]);

        $this->actingAs($facultyUser, 'sanctum')
            ->postJson("/api/faculty/students/{$student->id}/analyze-risk")
            ->assertStatus(503)
            ->assertJsonPath('status', false);
    }

    private function createAssignedStudent(): array
    {
        $facultyUser = User::factory()->create([
            'role' => 'faculty',
            'department' => 'Computer Science & Engineering',
            'faculty_id' => 'FAC-CSE-0045',
        ]);
        $faculty = Faculty::create([
            'user_id' => $facultyUser->id,
            'department' => $facultyUser->department,
            'designation' => 'Associate Professor',
        ]);
        $course = Course::create([
            'faculty_id' => $faculty->id,
            'course_code' => 'CSE 4103',
            'title' => 'Artificial Intelligence',
        ]);

        $studentUser = User::factory()->create([
            'role' => 'student',
            'department' => 'Computer Science & Engineering',
            'student_id' => 'CSE11220320852',
        ]);
        $student = Student::create([
            'user_id' => $studentUser->id,
            'student_number' => $studentUser->student_id,
            'department' => $studentUser->department,
            'program' => 'BSc in CSE',
        ]);

        foreach (['present', 'present', 'present', 'absent'] as $index => $status) {
            AttendanceRecord::create([
                'student_id' => $student->id,
                'course_id' => $course->id,
                'attendance_date' => now()->subDays($index),
                'status' => $status,
            ]);
        }

        PerformanceMetric::create([
            'student_id' => $student->id,
            'cgpa' => 2.8,
            'semester_gpa' => 2.7,
            'completed_credits' => 95,
        ]);
        AcademicRecord::create([
            'student_id' => $student->id,
            'course_id' => $course->id,
            'grade' => 'B',
            'semester' => 'Spring',
            'year' => 2026,
        ]);

        return [$facultyUser, $student, $course];
    }
}

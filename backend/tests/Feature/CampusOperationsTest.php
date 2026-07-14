<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\Faculty;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CampusOperationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_assessments_calculate_grade_and_credit_weighted_cgpa(): void
    {
        $facultyUser = User::factory()->create(['role' => 'faculty']);
        $faculty = Faculty::create(['user_id' => $facultyUser->id, 'faculty_number' => 'F-1']);
        $studentUser = User::factory()->create(['role' => 'student']);
        $student = Student::create(['user_id' => $studentUser->id, 'student_number' => 'S-1']);
        $course = Course::create(['faculty_id' => $faculty->id, 'course_code' => 'CSE-201', 'title' => 'Algorithms', 'department' => 'CSE', 'credit_hours' => 3]);
        CourseEnrollment::create(['course_id' => $course->id, 'student_id' => $student->id, 'semester' => 'Spring', 'year' => 2026]);
        Sanctum::actingAs($facultyUser);

        $this->putJson("/api/academic-management/courses/{$course->id}/assessments", [
            'semester' => 'Spring', 'year' => 2026,
            'records' => [['student_id' => $student->id, 'quiz_marks' => 12, 'assignment_marks' => 13, 'mid_marks' => 25, 'final_marks' => 30]],
        ])->assertOk();

        $this->assertDatabaseHas('assessment_records', ['student_id' => $student->id, 'total_marks' => 80]);
        $this->assertDatabaseHas('academic_records', ['student_id' => $student->id, 'grade' => 'A+']);
        $this->assertDatabaseHas('performance_metrics', ['student_id' => $student->id, 'cgpa' => 4]);
    }

    public function test_campus_services_are_role_scoped_and_student_can_submit_ticket(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $studentUser = User::factory()->create(['role' => 'student']);
        $student = Student::create(['user_id' => $studentUser->id, 'student_number' => 'S-2']);
        Sanctum::actingAs($admin);
        $this->postJson('/api/campus-services/events', ['title' => 'Registration Week', 'starts_on' => '2026-08-01', 'event_type' => 'registration', 'audience' => 'student'])->assertCreated();
        $this->postJson('/api/campus-services/fees', ['student_id' => $student->id, 'semester' => 'Fall', 'year' => 2026, 'amount_due' => 30000, 'amount_paid' => 10000])->assertOk();

        Sanctum::actingAs($studentUser);
        $this->postJson('/api/campus-services/tickets', ['category' => 'Accounts', 'subject' => 'Fee query', 'description' => 'Please verify my payment.', 'priority' => 'medium'])->assertCreated();
        $this->getJson('/api/campus-services')->assertOk()->assertJsonPath('data.events.0.title', 'Registration Week')->assertJsonPath('data.fees.0.status', 'partial')->assertJsonCount(1, 'data.tickets');
        $this->postJson('/api/campus-services/events', [])->assertForbidden();
    }

    public function test_unassigned_faculty_cannot_browse_department_students(): void
    {
        $facultyUser = User::factory()->create(['role' => 'faculty', 'department' => 'CSE']);
        Faculty::create(['user_id' => $facultyUser->id, 'faculty_number' => 'F-2']);
        $studentUser = User::factory()->create(['role' => 'student', 'department' => 'CSE']);
        Student::create(['user_id' => $studentUser->id, 'student_number' => 'S-3']);
        Sanctum::actingAs($facultyUser);
        $this->getJson('/api/faculty/student-monitoring')->assertOk()->assertJsonCount(0, 'data.students');
    }
}

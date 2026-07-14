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
        $this->getJson('/api/campus-services')->assertOk()->assertJsonFragment(['title' => 'Registration Week'])->assertJsonPath('data.fees.0.status', 'partial')->assertJsonCount(1, 'data.tickets');
        $this->postJson('/api/campus-services/events', [])->assertForbidden();
    }

    public function test_admin_manages_routines_and_conflicts_are_rejected(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $facultyUser = User::factory()->create(['role' => 'faculty']);
        $faculty = Faculty::create(['user_id' => $facultyUser->id]);
        $first = Course::create(['faculty_id' => $faculty->id, 'course_code' => 'CSE-301', 'title' => 'Networks', 'department' => 'CSE', 'credit_hours' => 3]);
        $second = Course::create(['faculty_id' => $faculty->id, 'course_code' => 'CSE-302', 'title' => 'Systems', 'department' => 'CSE', 'credit_hours' => 3]);
        Sanctum::actingAs($admin);
        $payload = ['course_id' => $first->id, 'semester' => 'Fall', 'year' => 2026, 'section' => '8A', 'day_of_week' => 1, 'starts_at' => '09:00', 'ends_at' => '10:30', 'room' => '405', 'class_type' => 'lecture'];
        $id = $this->postJson('/api/campus-services/schedules', $payload)->assertCreated()->json('data.id');
        $this->postJson('/api/campus-services/schedules', [...$payload, 'course_id' => $second->id, 'starts_at' => '10:00', 'ends_at' => '11:00', 'room' => '406'])->assertUnprocessable()->assertJsonValidationErrors('starts_at');
        $this->putJson("/api/campus-services/schedules/{$id}", [...$payload, 'starts_at' => '11:00', 'ends_at' => '12:30'])->assertOk();
        $this->deleteJson("/api/campus-services/schedules/{$id}")->assertOk();
    }

    public function test_admin_can_edit_and_delete_academic_calendar_event(): void
    {
        $admin = User::factory()->create(['role' => 'admin']); Sanctum::actingAs($admin);
        $payload = ['title' => 'Semester Begins', 'starts_on' => '2026-08-01', 'ends_on' => null, 'description' => 'First day', 'event_type' => 'academic', 'audience' => 'all', 'is_all_day' => true, 'recurrence' => 'none'];
        $id = $this->postJson('/api/campus-services/events', $payload)->assertCreated()->json('data.id');
        $this->putJson("/api/campus-services/events/{$id}", [...$payload, 'title' => 'Classes Begin'])->assertOk();
        $this->assertDatabaseHas('academic_events', ['id' => $id, 'title' => 'Classes Begin']);
        $this->deleteJson("/api/campus-services/events/{$id}")->assertOk();
        $this->assertDatabaseMissing('academic_events', ['id' => $id]);
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

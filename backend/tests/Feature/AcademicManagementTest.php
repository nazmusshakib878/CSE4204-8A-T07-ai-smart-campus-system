<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\Faculty;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcademicManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_update_assign_and_delete_course(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $faculty = $this->faculty('FAC-CSE-0101');

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/academic-management/courses', [
            'course_code' => 'CSE 4204',
            'title' => 'Mobile Computing Lab',
            'department' => 'Computer Science & Engineering',
            'credit_hours' => 1.5,
            'description' => 'Project lab',
            'faculty_id' => $faculty->id,
            'is_active' => true,
        ])->assertCreated()->assertJsonPath('data.faculty_id', $faculty->id);

        $courseId = $response->json('data.id');

        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/academic-management/courses/{$courseId}", [
                'course_code' => 'CSE 4204',
                'title' => 'Mobile Computing Laboratory',
                'department' => 'Computer Science & Engineering',
                'credit_hours' => 2,
                'description' => 'Updated project lab',
                'faculty_id' => $faculty->id,
                'is_active' => true,
            ])
            ->assertOk()
            ->assertJsonPath('data.title', 'Mobile Computing Laboratory');

        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/academic-management/courses/{$courseId}")
            ->assertOk();

        $this->assertDatabaseMissing('courses', ['id' => $courseId]);
    }

    public function test_assigned_faculty_can_enroll_and_enter_all_academic_data(): void
    {
        [$facultyUser, $faculty] = $this->facultyWithUser('FAC-CSE-0102');
        $course = Course::create([
            'faculty_id' => $faculty->id,
            'course_code' => 'CSE 4103',
            'title' => 'Artificial Intelligence',
            'department' => 'Computer Science & Engineering',
            'credit_hours' => 3,
            'is_active' => true,
        ]);
        $student = $this->student('CSE11220320852');

        $this->actingAs($facultyUser, 'sanctum')
            ->postJson("/api/academic-management/courses/{$course->id}/enrollments", [
                'student_ids' => [$student->id],
                'semester' => 'Spring',
                'year' => 2026,
            ])->assertOk();

        $this->actingAs($facultyUser, 'sanctum')
            ->putJson("/api/academic-management/courses/{$course->id}/attendance", [
                'attendance_date' => '2026-07-15',
                'records' => [['student_id' => $student->id, 'status' => 'present']],
            ])->assertOk();

        $this->actingAs($facultyUser, 'sanctum')
            ->putJson("/api/academic-management/courses/{$course->id}/grades", [
                'semester' => 'Spring',
                'year' => 2026,
                'records' => [['student_id' => $student->id, 'grade' => 'A-']],
            ])->assertOk();

        $this->actingAs($facultyUser, 'sanctum')
            ->putJson("/api/academic-management/courses/{$course->id}/students/{$student->id}/performance", [
                'semester' => 'Spring',
                'year' => 2026,
                'semester_gpa' => 3.65,
                'cgpa' => 3.55,
                'completed_credits' => 110,
            ])->assertOk();

        $this->assertDatabaseHas('course_enrollments', ['course_id' => $course->id, 'student_id' => $student->id]);
        $this->assertDatabaseHas('attendance_records', ['course_id' => $course->id, 'student_id' => $student->id, 'status' => 'present']);
        $this->assertDatabaseHas('academic_records', ['course_id' => $course->id, 'student_id' => $student->id, 'grade' => 'A-']);
        $this->assertDatabaseHas('performance_metrics', ['student_id' => $student->id, 'semester_gpa' => 3.65, 'cgpa' => 3.55]);

        $this->actingAs($facultyUser, 'sanctum')
            ->getJson("/api/academic-management/courses/{$course->id}/workspace")
            ->assertOk()
            ->assertJsonPath('data.enrollments.0.student.id', $student->id)
            ->assertJsonPath('data.enrollments.0.attendance_percentage', 100)
            ->assertJsonPath('data.enrollments.0.grades.0.grade', 'A-');
    }

    public function test_faculty_cannot_manage_another_faculty_course_or_create_courses(): void
    {
        [$firstUser] = $this->facultyWithUser('FAC-CSE-0103');
        [, $secondFaculty] = $this->facultyWithUser('FAC-CSE-0104');
        $course = Course::create([
            'faculty_id' => $secondFaculty->id,
            'course_code' => 'CSE 4201',
            'title' => 'Computer Networks',
            'department' => 'Computer Science & Engineering',
            'credit_hours' => 3,
            'is_active' => true,
        ]);

        $this->actingAs($firstUser, 'sanctum')
            ->getJson("/api/academic-management/courses/{$course->id}/workspace")
            ->assertForbidden();

        $this->actingAs($firstUser, 'sanctum')
            ->postJson('/api/academic-management/courses', [])
            ->assertForbidden();
    }

    public function test_academic_entry_rejects_students_not_enrolled_in_the_course(): void
    {
        [$facultyUser, $faculty] = $this->facultyWithUser('FAC-CSE-0105');
        $course = Course::create([
            'faculty_id' => $faculty->id,
            'course_code' => 'CSE 4202',
            'title' => 'Software Engineering',
            'department' => 'Computer Science & Engineering',
            'credit_hours' => 3,
            'is_active' => true,
        ]);
        $student = $this->student('CSE11220320853');

        $this->actingAs($facultyUser, 'sanctum')
            ->putJson("/api/academic-management/courses/{$course->id}/attendance", [
                'attendance_date' => '2026-07-15',
                'records' => [['student_id' => $student->id, 'status' => 'present']],
            ])
            ->assertUnprocessable()
            ->assertJsonPath('status', false);
    }

    private function faculty(string $facultyId): Faculty
    {
        return $this->facultyWithUser($facultyId)[1];
    }

    private function facultyWithUser(string $facultyId): array
    {
        $user = User::factory()->create([
            'role' => 'faculty',
            'faculty_id' => $facultyId,
            'department' => 'Computer Science & Engineering',
        ]);
        $faculty = Faculty::create([
            'user_id' => $user->id,
            'department' => $user->department,
            'designation' => 'Lecturer',
        ]);
        return [$user, $faculty];
    }

    private function student(string $studentNumber): Student
    {
        $user = User::factory()->create([
            'role' => 'student',
            'student_id' => $studentNumber,
            'department' => 'Computer Science & Engineering',
        ]);
        return Student::create([
            'user_id' => $user->id,
            'student_number' => $studentNumber,
            'department' => $user->department,
            'program' => 'BSc in CSE',
        ]);
    }
}

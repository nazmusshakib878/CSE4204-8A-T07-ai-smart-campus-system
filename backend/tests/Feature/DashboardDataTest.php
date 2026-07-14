<?php
namespace Tests\Feature;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\CourseSchedule;
use App\Models\Faculty;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
class DashboardDataTest extends TestCase {
 use RefreshDatabase;
 public function test_admin_dashboard_uses_database_counts_and_is_admin_only(): void {
  $admin=User::factory()->create(['role'=>'admin']);
  User::factory()->create(['role'=>'student','department'=>'CSE']);
  User::factory()->create(['role'=>'faculty','department'=>'CSE']);
  $student=User::factory()->create(['role'=>'student']);
  Sanctum::actingAs($admin);
  $this->getJson('/api/admin/dashboard')->assertOk()->assertJsonPath('data.stats.total_users',4)->assertJsonPath('data.stats.active_students',2)->assertJsonPath('data.stats.faculty_members',1);
  Sanctum::actingAs($student);
  $this->getJson('/api/admin/dashboard')->assertForbidden();
 }
 public function test_faculty_dashboard_is_scoped_to_assigned_courses(): void {
  $user=User::factory()->create(['role'=>'faculty']);
  $faculty=Faculty::create(['user_id'=>$user->id,'department'=>'CSE']);
  $studentUser=User::factory()->create(['role'=>'student']);
  $student=Student::create(['user_id'=>$studentUser->id,'student_number'=>'S-1']);
  $course=Course::create(['faculty_id'=>$faculty->id,'course_code'=>'CSE-101','title'=>'Programming']);
  CourseEnrollment::create(['course_id'=>$course->id,'student_id'=>$student->id,'semester'=>'Spring','year'=>2026]);
  CourseSchedule::create(['course_id'=>$course->id,'day_of_week'=>1,'starts_at'=>'09:00','ends_at'=>'10:30','room'=>'R-1']);
  Sanctum::actingAs($user);
  $this->getJson('/api/faculty/dashboard')->assertOk()->assertJsonPath('data.stats.total_students',1)->assertJsonPath('data.stats.total_courses',1)->assertJsonPath('data.stats.classes_this_week',1)->assertJsonPath('data.schedule.0.code','CSE-101');
 }
}

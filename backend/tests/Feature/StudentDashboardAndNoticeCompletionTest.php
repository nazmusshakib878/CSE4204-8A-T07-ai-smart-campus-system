<?php
namespace Tests\Feature;
use App\Models\AttendanceRecord;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\CourseSchedule;
use App\Models\Notice;
use App\Models\PerformanceMetric;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
class StudentDashboardAndNoticeCompletionTest extends TestCase {
 use RefreshDatabase;
 public function test_student_dashboard_returns_only_personal_academic_data():void {
  $user=User::factory()->create(['role'=>'student']);$student=Student::create(['user_id'=>$user->id,'student_number'=>'S-1','current_semester'=>8]);
  $course=Course::create(['course_code'=>'CSE-101','title'=>'Programming']);CourseEnrollment::create(['student_id'=>$student->id,'course_id'=>$course->id,'semester'=>'Spring','year'=>2026]);
  AttendanceRecord::create(['student_id'=>$student->id,'course_id'=>$course->id,'attendance_date'=>'2026-07-15','status'=>'present']);
  PerformanceMetric::create(['student_id'=>$student->id,'semester'=>'Spring','year'=>2026,'semester_gpa'=>3.5,'cgpa'=>3.4,'completed_credits'=>18]);
  CourseSchedule::create(['course_id'=>$course->id,'day_of_week'=>1,'starts_at'=>'09:00','ends_at'=>'10:00']);
  Sanctum::actingAs($user);
  $this->getJson('/api/student/dashboard')->assertOk()->assertJsonPath('data.summary.registered_courses',1)->assertJsonPath('data.summary.attendance_percentage',100)->assertJsonPath('data.summary.current_cgpa',3.4)->assertJsonPath('data.summary.current_semester','Spring 2026')->assertJsonPath('data.summary.current_semester_number',8)->assertJsonPath('data.courses.0.code','CSE-101')->assertJsonCount(1,'data.upcoming_classes');
 }
 public function test_notice_reads_expiry_archive_and_pagination_are_database_backed():void {
  $admin=User::factory()->create(['role'=>'admin']);$student=User::factory()->create(['role'=>'student']);
  $active=Notice::create(['user_id'=>$admin->id,'title'=>'Active','description'=>'Visible','category'=>'Academic','audience'=>'All','publish_date'=>now(),'email_delivery_status'=>'not_configured','sms_delivery_status'=>'not_configured']);
  Notice::create(['user_id'=>$admin->id,'title'=>'Expired','description'=>'Hidden','category'=>'Academic','audience'=>'All','publish_date'=>now()->subDays(2),'expires_at'=>now()->subDay()]);
  Sanctum::actingAs($student);
  $this->getJson('/api/notices?per_page=1')->assertOk()->assertJsonCount(1,'data')->assertJsonPath('data.0.id',$active->id)->assertJsonPath('meta.total',1)->assertJsonPath('meta.unread',1);
  $this->postJson('/api/notices/'.$active->id.'/read')->assertOk();
  $this->getJson('/api/notices')->assertOk()->assertJsonPath('data.0.is_read',true)->assertJsonPath('meta.unread',0);
  Sanctum::actingAs($admin);$this->patchJson('/api/notices/'.$active->id.'/archive')->assertOk();$this->assertNotNull($active->fresh()->archived_at);
 }
}

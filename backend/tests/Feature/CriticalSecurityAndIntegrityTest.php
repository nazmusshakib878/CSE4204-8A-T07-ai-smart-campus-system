<?php
namespace Tests\Feature;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\Department;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
class CriticalSecurityAndIntegrityTest extends TestCase {
 use RefreshDatabase;
 public function test_rejected_account_cannot_keep_using_an_existing_token():void {
  $user=User::factory()->create(['approval_status'=>'approved']);$token=$user->createToken('browser')->plainTextToken;$user->update(['approval_status'=>'rejected']);
  $this->withToken($token)->getJson('/api/profile')->assertForbidden()->assertJsonPath('status',false);
 }
 public function test_admin_rejection_revokes_all_user_tokens():void {
  $admin=User::factory()->create(['role'=>'admin','approval_status'=>'approved']);$user=User::factory()->create(['role'=>'student','approval_status'=>'approved']);$user->createToken('one');$user->createToken('two');Sanctum::actingAs($admin);
  $this->patchJson('/api/admin/users/'.$user->id.'/approval',['approval_status'=>'rejected'])->assertOk();$this->assertDatabaseMissing('personal_access_tokens',['tokenable_id'=>$user->id]);
 }
 public function test_profile_cannot_change_department_and_password_revokes_other_tokens():void {
  $user=User::factory()->create(['approval_status'=>'approved','department'=>'Computer Science & Engineering','password'=>Hash::make('OldPass!123')]);$current=$user->createToken('current');$other=$user->createToken('other');
  $this->withToken($current->plainTextToken)->putJson('/api/profile',['name'=>'Safe User','phone'=>'01712345678','department'=>'Electrical & Electronic Engineering'])->assertOk();$this->assertSame('Computer Science & Engineering',$user->fresh()->department);
  $this->withToken($current->plainTextToken)->putJson('/api/profile/password',['current_password'=>'OldPass!123','password'=>'NewPass!456','password_confirmation'=>'NewPass!456'])->assertOk();$this->assertDatabaseHas('personal_access_tokens',['id'=>$current->accessToken->id]);$this->assertDatabaseMissing('personal_access_tokens',['id'=>$other->accessToken->id]);
 }
 public function test_used_departments_and_courses_with_history_are_protected():void {
  $admin=User::factory()->create(['role'=>'admin','approval_status'=>'approved']);$department=Department::where('code','CSE')->firstOrFail();User::factory()->create(['department'=>$department->name]);Sanctum::actingAs($admin);
  $this->deleteJson('/api/admin/departments/'.$department->id)->assertStatus(409);$this->patchJson('/api/admin/departments/'.$department->id.'/status',['is_active'=>false])->assertOk()->assertJsonPath('data.is_active',false);
  $studentUser=User::factory()->create(['role'=>'student']);$student=Student::create(['user_id'=>$studentUser->id,'student_number'=>'SAFE-1']);$course=Course::create(['course_code'=>'SAFE-101','title'=>'Safe History','is_active'=>true]);CourseEnrollment::create(['course_id'=>$course->id,'student_id'=>$student->id,'semester'=>'Spring','year'=>2026]);
  $this->deleteJson('/api/academic-management/courses/'.$course->id)->assertStatus(409);$this->assertDatabaseHas('courses',['id'=>$course->id]);
 }
 public function test_notice_delivery_reports_disabled_when_providers_are_off():void {
  config(['notice_delivery.email_enabled'=>false,'notice_delivery.sms_enabled'=>false]);$admin=User::factory()->create(['role'=>'admin','approval_status'=>'approved']);Sanctum::actingAs($admin);
  $this->postJson('/api/notices',['title'=>'Delivery status','description'=>'Status check','category'=>'Academic','audience'=>'All'])->assertCreated()->assertJsonPath('data.email_delivery_status','disabled')->assertJsonPath('data.sms_delivery_status','disabled');
 }
}
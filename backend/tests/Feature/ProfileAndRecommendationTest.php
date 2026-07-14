<?php
namespace Tests\Feature;
use App\Models\Course;
use App\Models\Recommendation;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
class ProfileAndRecommendationTest extends TestCase {
 use RefreshDatabase;
 public function test_user_can_persist_profile_change_password_and_manage_photo():void {
  Storage::fake('public');$user=User::factory()->create(['password'=>Hash::make('OldPass!123')]);Sanctum::actingAs($user);
  $this->putJson('/api/profile',['name'=>'Updated User','phone'=>'01712345678','department'=>'Computer Science & Engineering'])->assertOk()->assertJsonPath('user.name','Updated User');
  $this->putJson('/api/profile/password',['current_password'=>'OldPass!123','password'=>'NewPass!456','password_confirmation'=>'NewPass!456'])->assertOk();
  $this->assertTrue(Hash::check('NewPass!456',$user->fresh()->password));
  $response=$this->postJson('/api/profile/photo',['photo'=>UploadedFile::fake()->createWithContent('avatar.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Y9ZQmcAAAAASUVORK5CYII='))])->assertOk();
  $path=$user->fresh()->profile_photo_path;$this->assertNotNull($path);Storage::disk('public')->assertExists($path);
  $this->deleteJson('/api/profile/photo')->assertOk()->assertJsonPath('user.profile_photo_url',null);Storage::disk('public')->assertMissing($path);
 }
 public function test_student_receives_real_rule_based_courses_when_no_advisor_recommendation_exists():void {
  $user=User::factory()->create(['role'=>'student','department'=>'Computer Science & Engineering']);Student::create(['user_id'=>$user->id,'student_number'=>'S-100','department'=>$user->department]);
  $course=Course::create(['course_code'=>'CSE-500','title'=>'Machine Learning','department'=>$user->department,'is_active'=>true]);
  Sanctum::actingAs($user);
  $this->getJson('/api/recommendations')->assertOk()->assertJsonPath('source','rule_based')->assertJsonPath('data.0.course_id',$course->id)->assertJsonPath('data.0.source','rule_based');
 }
 public function test_advisor_created_recommendations_take_priority():void {
  $user=User::factory()->create(['role'=>'student']);Student::create(['user_id'=>$user->id,'student_number'=>'S-101']);
  Recommendation::create(['title'=>'CSE-600 | Advisor Course','recommendation_type'=>'Advisor','target_user_id'=>$user->id,'target_user'=>$user->name,'score'=>98]);
  Sanctum::actingAs($user);
  $this->getJson('/api/recommendations')->assertOk()->assertJsonPath('source','advisor')->assertJsonCount(1,'data')->assertJsonPath('data.0.score',98);
 }
}

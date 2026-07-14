<?php
namespace Tests\Feature;
use App\Models\Department;
use App\Models\Notice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
class DepartmentAndNoticeEdgeCaseTest extends TestCase {
 use RefreshDatabase;
 public function test_department_management_rejects_duplicates_and_non_admins():void {
  $admin=User::factory()->create(['role'=>'admin']);$student=User::factory()->create(['role'=>'student']);
  Sanctum::actingAs($admin);$this->postJson('/api/admin/departments',['name'=>'Computer Science & Engineering','code'=>'CSE'])->assertUnprocessable();
  Sanctum::actingAs($student);$this->postJson('/api/admin/departments',['name'=>'New Department','code'=>'NEW'])->assertForbidden();
  $inactive=Department::create(['name'=>'Inactive Studies','code'=>'INS','is_active'=>false]);$this->getJson('/api/departments')->assertOk()->assertJsonMissing(['id'=>$inactive->id]);
 }
 public function test_notice_attachment_download_rejects_wrong_audience():void {
  Storage::fake('public');Storage::disk('public')->put('notice-attachments/private.pdf','private');
  $admin=User::factory()->create(['role'=>'admin']);$student=User::factory()->create(['role'=>'student','department'=>'Electrical & Electronic Engineering']);
  $notice=Notice::create(['user_id'=>$admin->id,'title'=>'CSE only','description'=>'Private department notice','category'=>'Academic','audience'=>'Department','target_department'=>'Computer Science & Engineering','target_role'=>'Students','publish_date'=>now(),'attachment_path'=>'notice-attachments/private.pdf','attachment_name'=>'private.pdf','attachment_mime'=>'application/pdf']);
  Sanctum::actingAs($student);$this->get('/api/notices/'.$notice->id.'/attachment',['Accept'=>'application/json'])->assertNotFound();
  Sanctum::actingAs($admin);$this->get('/api/notices/'.$notice->id.'/attachment')->assertOk();
 }
}

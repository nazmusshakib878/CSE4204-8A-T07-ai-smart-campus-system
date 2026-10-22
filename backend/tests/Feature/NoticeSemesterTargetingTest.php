<?php

namespace Tests\Feature;

use App\Models\Notice;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NoticeSemesterTargetingTest extends TestCase
{
    use RefreshDatabase;

    public function test_students_only_receive_department_notices_for_their_own_semester(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $notices = collect(range(1, 8))->mapWithKeys(function (int $semester) use ($admin) {
            $notice = Notice::create([
                'user_id' => $admin->id,
                'title' => "Semester {$semester} notice",
                'description' => 'Restricted academic information.',
                'publish_date' => now(),
                'category' => 'Academic',
                'audience' => 'Department',
                'target_department' => 'Computer Science & Engineering',
                'target_role' => 'Students',
                'target_semester' => (string) $semester,
            ]);

            return [$semester => $notice];
        });
        $general = Notice::create([
            'user_id' => $admin->id,
            'title' => 'CSE student notice',
            'description' => 'Information for every CSE student.',
            'publish_date' => now(),
            'category' => 'Academic',
            'audience' => 'Department',
            'target_department' => 'Computer Science & Engineering',
            'target_role' => 'Students',
        ]);

        foreach (range(1, 8) as $semester) {
            $user = User::factory()->create(['role' => 'student', 'department' => 'Computer Science & Engineering']);
            Student::create(['user_id' => $user->id, 'student_number' => "CSE-{$semester}", 'current_semester' => $semester, 'section' => "{$semester}A"]);
            Sanctum::actingAs($user);

            $this->getJson('/api/notices')
                ->assertOk()
                ->assertJsonCount(2, 'data')
                ->assertJsonFragment(['id' => $notices[$semester]->id])
                ->assertJsonFragment(['id' => $general->id]);

            $otherSemester = $semester === 8 ? 1 : $semester + 1;
            $this->getJson('/api/notices/'.$notices[$otherSemester]->id)->assertForbidden();
        }
    }

    public function test_target_semester_must_be_a_valid_academic_semester(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin);

        $this->postJson('/api/notices', [
            'title' => 'Invalid semester scope',
            'description' => 'This must not be stored.',
            'category' => 'Academic',
            'audience' => 'Department',
            'target_department' => 'Computer Science & Engineering',
            'target_role' => 'Students',
            'target_semester' => 9,
        ])->assertUnprocessable()->assertJsonValidationErrors('target_semester');
    }

    public function test_students_cannot_access_a_notice_for_another_section_in_the_same_semester(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $notice = Notice::create(['user_id' => $admin->id, 'title' => '8A only', 'description' => 'Section restricted.', 'publish_date' => now(), 'category' => 'Exam', 'audience' => 'Department', 'target_department' => 'Computer Science & Engineering', 'target_role' => 'Students', 'target_semester' => '8', 'target_section' => '8A']);
        $user = User::factory()->create(['role' => 'student', 'department' => 'Computer Science & Engineering']);
        Student::create(['user_id' => $user->id, 'student_number' => 'CSE-8B', 'current_semester' => 8, 'section' => '8B']);

        Sanctum::actingAs($user);
        $this->getJson('/api/notices')->assertOk()->assertJsonCount(0, 'data');
        $this->getJson('/api/notices/'.$notice->id)->assertForbidden();
    }
}

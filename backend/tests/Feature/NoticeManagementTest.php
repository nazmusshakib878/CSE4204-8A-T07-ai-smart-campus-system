<?php

namespace Tests\Feature;

use App\Models\Notice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NoticeManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_faculty_can_send_an_individual_academic_notice(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'faculty']));

        $this->postJson('/api/notices', [
            'title' => 'Academic notice for Fatema Khanam',
            'description' => 'Please contact your faculty advisor.',
            'category' => 'Academic Risk',
            'audience' => 'Individual',
            'recipient_name' => 'Fatema Khanam',
            'recipient_reference' => '2021-1-60-002',
        ])
            ->assertCreated()
            ->assertJsonPath('data.recipient_reference', '2021-1-60-002');

        $this->assertDatabaseHas('notices', [
            'title' => 'Academic notice for Fatema Khanam',
            'audience' => 'Individual',
            'recipient_reference' => '2021-1-60-002',
        ]);
    }

    public function test_admin_can_list_and_delete_notices(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $notice = Notice::create([
            'user_id' => $admin->id,
            'title' => 'Campus notice',
            'description' => 'A campus-wide announcement.',
            'publish_date' => now(),
            'category' => 'Academic',
            'audience' => 'All',
        ]);

        Sanctum::actingAs($admin);

        $this->getJson('/api/notices')
            ->assertOk()
            ->assertJsonPath('data.0.id', $notice->id);

        $this->deleteJson("/api/notices/{$notice->id}")
            ->assertOk()
            ->assertJsonPath('message', 'Notice deleted successfully.');

        $this->assertDatabaseMissing('notices', ['id' => $notice->id]);
    }

    public function test_students_cannot_publish_notices(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'student']));

        $this->postJson('/api/notices', [
            'title' => 'Unauthorized notice',
            'description' => 'Students cannot publish notices.',
            'category' => 'Academic',
            'audience' => 'All',
        ])->assertForbidden();

        $this->assertDatabaseCount('notices', 0);
    }
}

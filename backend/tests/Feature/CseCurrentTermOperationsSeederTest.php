<?php

namespace Tests\Feature;

use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\CseCourseCatalogSeeder;
use Database\Seeders\CseCurrentTermOperationsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CseCurrentTermOperationsSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_seeder_is_idempotent_and_student_can_view_current_routines(): void
    {
        Carbon::setTestNow('2026-08-08 12:00:00');
        $user = User::factory()->create(['role' => 'student']);
        $student = Student::create([
            'user_id' => $user->id,
            'student_number' => 'CSE1122032069',
            'department' => 'Civil Engineering',
            'program' => 'BSc',
            'current_semester' => 1,
        ]);

        $this->seed(CseCourseCatalogSeeder::class);
        $this->seed(CseCurrentTermOperationsSeeder::class);
        $this->seed(CseCurrentTermOperationsSeeder::class);

        $this->assertSame(2, $student->enrollments()->where('semester', 'Fall')->where('year', 2026)->count());
        $this->assertDatabaseCount('course_schedules', 2);
        $this->assertDatabaseCount('exam_routines', 2);

        Sanctum::actingAs($user);
        $this->getJson('/api/campus-services')
            ->assertOk()
            ->assertJsonCount(2, 'data.schedules')
            ->assertJsonCount(2, 'data.exams')
            ->assertJsonFragment(['semester' => 'Fall', 'section' => '1A']);
    }
}
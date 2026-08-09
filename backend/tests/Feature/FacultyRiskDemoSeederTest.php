<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\Faculty;
use App\Models\RiskAlert;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\FacultyRiskDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FacultyRiskDemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_is_idempotent_and_risk_alerts_are_calculated_from_database_records(): void
    {
        $facultyUser = User::factory()->create([
            'role' => 'faculty',
            'department' => 'CSE',
            'approval_status' => 'approved',
        ]);
        Faculty::create([
            'user_id' => $facultyUser->id,
            'department' => 'CSE',
            'designation' => 'Lecturer',
        ]);
        $facultyUser->createToken('faculty-demo-test');

        $this->seed(FacultyRiskDemoSeeder::class);
        $this->seed(FacultyRiskDemoSeeder::class);

        $demoStudents = Student::query()
            ->whereHas('user', fn ($query) => $query->where('email', 'like', 'risk.demo.%'))
            ->get();

        $this->assertCount(3, $demoStudents);
        $this->assertSame(75, AttendanceRecord::whereIn('student_id', $demoStudents->pluck('id'))->count());
        $this->assertSame(3, RiskAlert::whereIn('student_id', $demoStudents->pluck('id'))->where('model', 'faculty-risk-demo')->count());

        $response = $this->actingAs($facultyUser, 'sanctum')
            ->getJson('/api/faculty/student-monitoring')
            ->assertOk()
            ->assertJsonPath('data.summary.total', 3)
            ->assertJsonPath('data.summary.high_risk', 1)
            ->assertJsonPath('data.summary.medium_risk', 1)
            ->assertJsonPath('data.summary.on_track', 1);

        $students = collect($response->json('data.students'))->keyBy('name');
        $this->assertSame(52, $students['Ayesha Rahman']['attendance']);
        $this->assertSame(1.85, $students['Ayesha Rahman']['cgpa']);
        $this->assertSame('high', $students['Ayesha Rahman']['priority']);
        $this->assertSame(68, $students['Farhan Ahmed']['attendance']);
        $this->assertSame(2.3, $students['Farhan Ahmed']['cgpa']);
        $this->assertSame('medium', $students['Farhan Ahmed']['priority']);
        $this->assertSame(88, $students['Nusrat Jahan']['attendance']);
        $this->assertSame(3.45, $students['Nusrat Jahan']['cgpa']);
        $this->assertSame('low', $students['Nusrat Jahan']['priority']);
    }
}

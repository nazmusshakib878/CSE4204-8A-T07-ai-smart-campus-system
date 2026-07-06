<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_requires_all_fields(): void
    {
        $this->postJson('/api/register')
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'name',
                'email',
                'phone',
                'password',
                'password_confirmation',
                'role',
                'department',
            ]);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_registration_rejects_invalid_input(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'A',
            'email' => 'not-an-email',
            'phone' => 'bad-phone',
            'password' => 'weak',
            'password_confirmation' => 'different',
            'role' => 'visitor',
            'department' => '',
            'student_id' => 'bad-id',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonPath('status', false)
            ->assertJsonPath('message', 'Please correct the invalid fields and try again.')
            ->assertJsonValidationErrors([
                'name',
                'email',
                'phone',
                'password',
                'password_confirmation',
                'role',
                'department',
            ]);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_student_registration_normalizes_valid_input_and_stays_pending(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => '  Ayesha Rahman  ',
            'email' => '  AYESHA@EXAMPLE.COM  ',
            'phone' => ' 01712345678 ',
            'password' => 'StrongPass1!',
            'password_confirmation' => 'StrongPass1!',
            'role' => 'student',
            'department' => 'Computer Science & Engineering',
            'student_id' => ' ce66334459156 ',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('user.name', 'Ayesha Rahman')
            ->assertJsonPath('user.email', 'ayesha@example.com')
            ->assertJsonPath('user.phone', '01712345678')
            ->assertJsonPath('user.approval_status', 'pending')
            ->assertJsonMissingPath('token');

        $this->assertDatabaseHas('users', [
            'name' => 'Ayesha Rahman',
            'email' => 'ayesha@example.com',
            'phone' => '01712345678',
            'department' => 'Computer Science & Engineering',
            'student_id' => 'CE66334459156',
            'approval_status' => 'pending',
        ]);
    }

    public function test_faculty_registration_requires_valid_faculty_id(): void
    {
        $this->postJson('/api/register', [
            'name' => 'Dr. Farhana Islam',
            'email' => 'farhana@example.com',
            'phone' => '01712345679',
            'password' => 'StrongPass1!',
            'password_confirmation' => 'StrongPass1!',
            'role' => 'faculty',
            'department' => 'Electrical & Electronic Engineering',
            'faculty_id' => 'FAC-EEE-0145',
        ])
            ->assertCreated()
            ->assertJsonPath('user.faculty_id', 'FAC-EEE-0145')
            ->assertJsonPath('user.phone', '01712345679')
            ->assertJsonPath('user.approval_status', 'pending');
    }

    public function test_public_registration_rejects_admin_role(): void
    {
        $this->postJson('/api/register', [
            'name' => 'System Admin',
            'email' => 'admin@example.com',
            'phone' => '01712345670',
            'password' => 'StrongPass1!',
            'password_confirmation' => 'StrongPass1!',
            'role' => 'admin',
            'department' => 'ADMIN',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['role']);
    }

    public function test_login_rejects_missing_or_malformed_input(): void
    {
        $this->postJson('/api/login', [
            'email' => 'invalid-email',
            'password' => '',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_an_approved_user_can_login_and_access_protected_routes(): void
    {
        $user = User::factory()->create([
            'email' => 'student@example.com',
            'password' => 'secret-password',
        ]);

        $loginResponse = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'secret-password',
        ]);

        $loginResponse
            ->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonStructure(['token']);

        $this->withToken($loginResponse->json('token'))
            ->getJson('/api/profile')
            ->assertOk()
            ->assertJsonPath('user.email', $user->email);
    }

    public function test_invalid_credentials_are_rejected_as_unauthorized(): void
    {
        User::factory()->create([
            'email' => 'student@example.com',
            'password' => 'correct-password',
        ]);

        $this->postJson('/api/login', [
            'email' => 'student@example.com',
            'password' => 'wrong-password',
        ])
            ->assertUnauthorized()
            ->assertJson([
                'status' => false,
                'message' => 'The provided credentials are incorrect.',
            ]);
    }

    public function test_pending_users_cannot_login(): void
    {
        User::factory()->create([
            'email' => 'pending@example.com',
            'password' => 'secret-password',
            'approval_status' => 'pending',
        ]);

        $this->postJson('/api/login', [
            'email' => 'pending@example.com',
            'password' => 'secret-password',
        ])
            ->assertForbidden()
            ->assertJson([
                'status' => false,
                'message' => 'Your account is pending administrator approval.',
            ]);
    }

    public function test_only_admin_users_can_create_admin_accounts(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'approval_status' => 'approved',
        ]);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/admin/create-admin', [
                'name' => 'System Admin',
                'email' => 'system-admin@example.com',
                'phone' => '01712345671',
                'password' => 'StrongPass1!',
                'admin_id' => 'ADM-045',
            ])
            ->assertCreated()
            ->assertJsonPath('user.role', 'admin')
            ->assertJsonPath('user.phone', '01712345671')
            ->assertJsonPath('user.admin_id', 'ADM-045')
            ->assertJsonPath('user.approval_status', 'approved');

        $this->assertDatabaseHas('users', [
            'email' => 'system-admin@example.com',
            'phone' => '01712345671',
            'admin_id' => 'ADM-045',
            'role' => 'admin',
            'approval_status' => 'approved',
        ]);
    }

    public function test_admin_can_list_and_approve_pending_users(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'approval_status' => 'approved',
        ]);
        $student = User::factory()->create([
            'role' => 'student',
            'approval_status' => 'pending',
            'student_id' => 'CE66334459156',
        ]);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/pending-users')
            ->assertOk()
            ->assertJsonPath('data.0.id', $student->id);

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/admin/users/{$student->id}/approval", [
                'approval_status' => 'approved',
            ])
            ->assertOk()
            ->assertJsonPath('user.approval_status', 'approved');

        $this->assertDatabaseHas('users', [
            'id' => $student->id,
            'approval_status' => 'approved',
        ]);
    }

    public function test_guests_cannot_access_protected_api_routes(): void
    {
        $this->getJson('/api/profile')
            ->assertUnauthorized()
            ->assertJson([
                'status' => false,
                'message' => 'Authentication failed. Please login first.',
            ]);

        $this->getJson('/api/learning-resources')
            ->assertUnauthorized();

        $this->getJson('/api/tasks')
            ->assertUnauthorized();

        $this->getJson('/api/recommendations')
            ->assertUnauthorized();
    }

    public function test_logout_revokes_the_current_access_token(): void
    {
        $user = User::factory()->create([
            'password' => 'secret-password',
        ]);

        $token = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'secret-password',
        ])->json('token');

        $this->withToken($token)
            ->postJson('/api/logout')
            ->assertOk()
            ->assertJsonPath('status', true);

        $this->assertDatabaseCount('personal_access_tokens', 0);

        Auth::forgetGuards();

        $this->withToken($token)
            ->getJson('/api/profile')
            ->assertUnauthorized();
    }
}
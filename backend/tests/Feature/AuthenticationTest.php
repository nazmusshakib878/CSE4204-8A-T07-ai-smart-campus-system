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
                'password',
                'password_confirmation',
                'role',
            ]);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_registration_rejects_invalid_input(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'A',
            'email' => 'not-an-email',
            'password' => 'weak',
            'password_confirmation' => 'different',
            'role' => 'visitor',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonPath('status', false)
            ->assertJsonPath('message', 'Please correct the invalid fields and try again.')
            ->assertJsonValidationErrors([
                'name',
                'email',
                'password',
                'password_confirmation',
                'role',
            ]);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_registration_normalizes_valid_input(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => '  Ayesha Rahman  ',
            'email' => '  AYESHA@EXAMPLE.COM  ',
            'password' => 'StrongPass1!',
            'password_confirmation' => 'StrongPass1!',
            'role' => 'student',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('user.name', 'Ayesha Rahman')
            ->assertJsonPath('user.email', 'ayesha@example.com');

        $this->assertDatabaseHas('users', [
            'name' => 'Ayesha Rahman',
            'email' => 'ayesha@example.com',
        ]);
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

    public function test_unapproved_users_cannot_login(): void
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
            ->assertJsonPath('status', false);
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

        // Each real API call starts with a fresh guard. Reset the guard here
        // because the feature-test application handles both calls in-process.
        Auth::forgetGuards();

        $this->withToken($token)
            ->getJson('/api/profile')
            ->assertUnauthorized();
    }
}

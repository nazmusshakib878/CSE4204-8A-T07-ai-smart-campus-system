<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_sends_reset_email_for_a_valid_email(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'student@example.com',
            'approval_status' => 'approved',
        ]);

        $response = $this->postJson('/api/forgot-password', [
            'email' => 'student@example.com',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonPath('message', 'If an account with that email exists, password reset instructions have been sent.');

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_forgot_password_returns_a_safe_response_for_unknown_email(): void
    {
        Notification::fake();

        $response = $this->postJson('/api/forgot-password', [
            'email' => 'missing@example.com',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonPath('message', 'If an account with that email exists, password reset instructions have been sent.');

        Notification::assertNothingSent();
    }

    public function test_user_can_reset_password_with_a_valid_token(): void
    {
        $user = User::factory()->create([
            'email' => 'reset@example.com',
            'password' => 'OldPass1!',
            'approval_status' => 'approved',
        ]);

        $token = Password::broker()->createToken($user);

        $response = $this->postJson('/api/reset-password', [
            'email' => $user->email,
            'token' => $token,
            'password' => 'NewPass1!',
            'password_confirmation' => 'NewPass1!',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonPath('message', 'Your password has been reset successfully.');

        $this->assertTrue(Hash::check('NewPass1!', $user->fresh()->password));
    }

    public function test_user_receives_a_friendly_error_for_an_invalid_reset_token(): void
    {
        $user = User::factory()->create([
            'email' => 'invalid-token@example.com',
            'approval_status' => 'approved',
        ]);

        $response = $this->postJson('/api/reset-password', [
            'email' => $user->email,
            'token' => 'not-a-valid-token',
            'password' => 'NewPass1!',
            'password_confirmation' => 'NewPass1!',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonPath('status', false)
            ->assertJsonPath('message', 'This password reset link is invalid or has expired. Please request a new one.');
    }

    public function test_reset_password_validates_password_confirmation(): void
    {
        $user = User::factory()->create([
            'email' => 'confirmation@example.com',
            'approval_status' => 'approved',
        ]);

        $token = Password::broker()->createToken($user);

        $this->postJson('/api/reset-password', [
            'email' => $user->email,
            'token' => $token,
            'password' => 'NewPass1!',
            'password_confirmation' => 'Mismatch1!',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['password_confirmation']);
    }
}
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $request->merge([
            'name' => trim((string) $request->input('name')),
            'email' => Str::lower(trim((string) $request->input('email'))),
        ]);

        $validatedData = $request->validate(
            [
                'name' => ['required', 'string', 'min:2', 'max:255'],
                'email' => ['required', 'string', 'email:rfc', 'max:255', Rule::unique(User::class)],
                'password' => [
                    'required',
                    'string',
                    'max:255',
                    Password::min(8)->letters()->mixedCase()->numbers()->symbols(),
                ],
                'password_confirmation' => ['required', 'string', 'max:255', 'same:password'],
                'role' => ['required', Rule::in(['student', 'faculty', 'admin'])],
            ],
            [
                'name.required' => 'Please enter your full name.',
                'name.min' => 'Your full name must be at least 2 characters.',
                'email.required' => 'Please enter your email address.',
                'email.email' => 'Please enter a valid email address.',
                'email.unique' => 'An account with this email address already exists.',
                'password.required' => 'Please create a password.',
                'password_confirmation.required' => 'Please confirm your password.',
                'password_confirmation.same' => 'The password confirmation does not match.',
                'role.required' => 'Please select an account role.',
                'role.in' => 'Please select a valid account role.',
            ]
        );

        $user = User::create([
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'password' => Hash::make($validatedData['password']),
            'role' => $validatedData['role'],
            'approval_status' => 'approved',
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => true,
            'message' => 'User registered successfully.',
            'user' => $user,
            'token' => $token,
            'token_type' => 'Bearer',
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $request->merge([
            'email' => Str::lower(trim((string) $request->input('email'))),
        ]);

        $validatedData = $request->validate(
            [
                'email' => ['required', 'string', 'email:rfc', 'max:255'],
                'password' => ['required', 'string', 'max:255'],
            ],
            [
                'email.required' => 'Please enter your email address.',
                'email.email' => 'Please enter a valid email address.',
                'password.required' => 'Please enter your password.',
            ]
        );

        $user = User::where('email', $validatedData['email'])->first();

        if (! $user || ! Hash::check($validatedData['password'], $user->password)) {
            return response()->json([
                'status' => false,
                'message' => 'The provided credentials are incorrect.',
            ], 401);
        }

        if ($user->approval_status !== 'approved') {
            return response()->json([
                'status' => false,
                'message' => 'Your account is not approved yet.',
            ], 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => true,
            'message' => 'User logged in successfully.',
            'user' => $user,
            'token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    public function profile(Request $request): JsonResponse
    {
        return response()->json([
            'status' => true,
            'message' => 'User profile retrieved successfully.',
            'user' => $request->user(),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json([
            'status' => true,
            'message' => 'User logged out successfully.',
        ]);
    }
}

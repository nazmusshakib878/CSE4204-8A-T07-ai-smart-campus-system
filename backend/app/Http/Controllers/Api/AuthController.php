<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Faculty;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    private function ensureAdmin(Request $request): ?JsonResponse
    {
        if ($request->user()?->role !== 'admin') {
            return response()->json([
                'status' => false,
                'message' => 'Only administrators can access this resource.',
            ], 403);
        }

        return null;
    }

    public function register(Request $request): JsonResponse
    {
        $request->merge([
            'name' => trim((string) $request->input('name')),
            'email' => Str::lower(trim((string) $request->input('email'))),
            'phone' => trim((string) $request->input('phone')),
            'department' => trim((string) $request->input('department')),
            'student_id' => Str::upper(trim((string) $request->input('student_id'))),
            'faculty_id' => Str::upper(trim((string) $request->input('faculty_id'))),
        ]);

        $validatedData = $request->validate(
            [
                'name' => ['required', 'string', 'min:2', 'max:255'],
                'email' => ['required', 'string', 'email:rfc', 'max:255', Rule::unique(User::class)],
                'phone' => ['required', 'string', 'max:20', 'regex:/^\+?[0-9]{10,15}$/'],
                'password' => [
                    'required',
                    'string',
                    'max:255',
                    Password::min(8)->letters()->mixedCase()->numbers()->symbols(),
                ],
                'password_confirmation' => ['required', 'string', 'max:255', 'same:password'],
                'role' => ['required', Rule::in(['student', 'faculty'])],
                'department' => ['required', 'string', 'max:255', Rule::exists('departments', 'name')->where('is_active', true)],
                'student_id' => [
                    'required_if:role,student',
                    'nullable',
                    'string',
                    'regex:/^[A-Z]{2,5}\d{8,12}$/',
                    Rule::unique(User::class, 'student_id'),
                ],
                'faculty_id' => [
                    'required_if:role,faculty',
                    'nullable',
                    'string',
                    'regex:/^FAC-[A-Z]{2,5}-\d{4}$/',
                    Rule::unique(User::class, 'faculty_id'),
                ],
            ],
            [
                'name.required' => 'Please enter your full name.',
                'name.min' => 'Your full name must be at least 2 characters.',
                'email.required' => 'Please enter your email address.',
                'email.email' => 'Please enter a valid email address.',
                'email.unique' => 'An account with this email address already exists.',
                'phone.required' => 'Please enter your phone number.',
                'phone.regex' => 'Phone number must be 10 to 15 digits and may start with +.',
                'password.required' => 'Please create a password.',
                'password_confirmation.required' => 'Please confirm your password.',
                'password_confirmation.same' => 'The password confirmation does not match.',
                'role.required' => 'Please select an account role.',
                'role.in' => 'Please select Student or Faculty.',
                'department.required' => 'Please enter your department.',
                'student_id.required_if' => 'Please enter your Student ID.',
                'student_id.regex' => 'Student ID must use 2-5 uppercase letters followed by 8-12 digits, like CE66334459156.',
                'student_id.unique' => 'An account with this Student ID already exists.',
                'faculty_id.required_if' => 'Please enter your Faculty ID.',
                'faculty_id.regex' => 'Faculty ID must look like FAC-CSE-0045, FAC-EEE-0145, or FAC-CE-0045.',
                'faculty_id.unique' => 'An account with this Faculty ID already exists.',
            ]
        );

        $user = DB::transaction(function () use ($validatedData) {
            $user = User::create([
                'name' => $validatedData['name'],
                'email' => $validatedData['email'],
                'phone' => $validatedData['phone'],
                'password' => Hash::make($validatedData['password']),
                'role' => $validatedData['role'],
                'department' => $validatedData['department'],
                'student_id' => $validatedData['role'] === 'student' ? $validatedData['student_id'] : null,
                'faculty_id' => $validatedData['role'] === 'faculty' ? $validatedData['faculty_id'] : null,
                'approval_status' => 'pending',
            ]);
    
            if ($user->role === 'student') {
                Student::firstOrCreate(
                    ['user_id' => $user->id],
                    [
                        'student_number' => $user->student_id,
                        'department' => $user->department,
                        'program' => $user->department,
                    ]
                );
            } else {
                Faculty::firstOrCreate(
                    ['user_id' => $user->id],
                    [
                        'department' => $user->department,
                        'designation' => 'Faculty Member',
                    ]
                );
            }

            return $user;
        });

        return response()->json([
            'status' => true,
            'message' => 'Registration submitted successfully. Your account is pending administrator approval.',
            'user' => $user,
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

        if ($user->approval_status === 'pending') {
            return response()->json([
                'status' => false,
                'message' => 'Your account is pending administrator approval.',
            ], 403);
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

    public function createAdmin(Request $request): JsonResponse
    {
        if ($response = $this->ensureAdmin($request)) {
            return $response;
        }

        $request->merge([
            'name' => trim((string) $request->input('name')),
            'email' => Str::lower(trim((string) $request->input('email'))),
            'phone' => trim((string) $request->input('phone')),
            'admin_id' => Str::upper(trim((string) $request->input('admin_id'))),
        ]);

        $validatedData = $request->validate(
            [
                'name' => ['required', 'string', 'min:2', 'max:255'],
                'email' => ['required', 'string', 'email:rfc', 'max:255', Rule::unique(User::class)],
                'phone' => ['required', 'string', 'max:20', 'regex:/^\+?[0-9]{10,15}$/'],
                'password' => [
                    'required',
                    'string',
                    'max:255',
                    Password::min(8)->letters()->mixedCase()->numbers()->symbols(),
                ],
                'admin_id' => ['required', 'string', 'regex:/^ADM-\d{3}$/', Rule::unique(User::class, 'admin_id')],
            ],
            [
                'name.required' => 'Please enter the admin name.',
                'email.required' => 'Please enter the admin email address.',
                'email.email' => 'Please enter a valid email address.',
                'email.unique' => 'An account with this email address already exists.',
                'phone.required' => 'Please enter the admin phone number.',
                'phone.regex' => 'Phone number must be 10 to 15 digits and may start with +.',
                'password.required' => 'Please create a password.',
                'admin_id.required' => 'Please enter the Admin ID.',
                'admin_id.regex' => 'Admin ID must look like ADM-045.',
                'admin_id.unique' => 'An account with this Admin ID already exists.',
            ]
        );

        $admin = User::create([
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'phone' => $validatedData['phone'],
            'password' => Hash::make($validatedData['password']),
            'role' => 'admin',
            'admin_id' => $validatedData['admin_id'],
            'approval_status' => 'approved',
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Admin account created successfully.',
            'user' => $admin,
        ], 201);
    }

    public function pendingUsers(Request $request): JsonResponse
    {
        if ($response = $this->ensureAdmin($request)) {
            return $response;
        }

        $users = User::whereIn('role', ['student', 'faculty'])
            ->where('approval_status', 'pending')
            ->latest()
            ->get();

        return response()->json([
            'status' => true,
            'data' => $users,
        ]);
    }

    public function updateApprovalStatus(Request $request, User $user): JsonResponse
    {
        if ($response = $this->ensureAdmin($request)) {
            return $response;
        }

        $validatedData = $request->validate([
            'approval_status' => ['required', Rule::in(['approved', 'rejected'])],
        ]);

        if (! in_array($user->role, ['student', 'faculty'], true)) {
            return response()->json([
                'status' => false,
                'message' => 'Only student and faculty accounts can be approved here.',
            ], 422);
        }

        $user->update([
            'approval_status' => $validatedData['approval_status'],
        ]);

        if ($validatedData['approval_status'] === 'rejected') {
            $user->tokens()->delete();
        }

        return response()->json([
            'status' => true,
            'message' => 'User approval status updated successfully.',
            'user' => $user,
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

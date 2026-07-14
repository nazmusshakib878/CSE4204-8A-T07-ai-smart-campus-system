<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Department;
use App\Models\Notice;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class DepartmentController extends Controller
{
    private function ensureAdmin(Request $request): ?JsonResponse
    {
        if ($request->user()?->role !== 'admin') {
            return response()->json([
                'status' => false,
                'message' => 'Only administrators can manage departments.',
            ], 403);
        }

        return null;
    }

    public function index(): JsonResponse
    {
        return response()->json([
            'status' => true,
            'data' => Department::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function adminIndex(Request $request): JsonResponse
    {
        if ($response = $this->ensureAdmin($request)) {
            return $response;
        }

        return response()->json([
            'status' => true,
            'data' => Department::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        if ($response = $this->ensureAdmin($request)) {
            return $response;
        }

        $request->merge([
            'name' => trim((string) $request->input('name')),
            'code' => Str::upper(trim((string) $request->input('code'))),
        ]);

        $validatedData = $request->validate(
            [
                'name' => ['required', 'string', 'min:2', 'max:255', Rule::unique(Department::class, 'name')],
                'code' => ['required', 'string', 'min:2', 'max:10', 'regex:/^[A-Z]{2,10}$/', Rule::unique(Department::class, 'code')],
            ],
            [
                'name.required' => 'Please enter the department name.',
                'name.unique' => 'This department already exists.',
                'code.required' => 'Please enter the department code.',
                'code.regex' => 'Department code must use letters only, like CSE or CE.',
                'code.unique' => 'This department code already exists.',
            ]
        );

        $department = Department::create([
            'name' => $validatedData['name'],
            'code' => $validatedData['code'],
            'is_active' => true,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Department created successfully.',
            'data' => $department,
        ], 201);
    }

    public function updateStatus(Request $request, Department $department): JsonResponse
    {
        if ($response = $this->ensureAdmin($request)) return $response;

        $data = $request->validate(['is_active' => ['required', 'boolean']]);
        $department->update(['is_active' => $data['is_active']]);

        return response()->json([
            'status' => true,
            'message' => $department->is_active ? 'Department activated successfully.' : 'Department archived successfully.',
            'data' => $department->fresh(),
        ]);
    }

    public function destroy(Request $request, Department $department): JsonResponse
    {
        if ($response = $this->ensureAdmin($request)) return $response;

        $inUse = User::where('department', $department->name)->exists()
            || Course::where('department', $department->name)->exists()
            || Notice::where('target_department', $department->name)->exists();

        if ($inUse) {
            return response()->json([
                'status' => false,
                'message' => 'This department is in use and cannot be deleted. Archive it instead.',
            ], 409);
        }

        $department->delete();

        return response()->json(['status' => true, 'message' => 'Unused department deleted successfully.']);
    }
}
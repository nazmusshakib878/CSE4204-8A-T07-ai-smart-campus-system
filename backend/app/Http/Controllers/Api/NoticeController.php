<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class NoticeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'data' => Notice::with('author:id,name')
                ->latest('publish_date')
                ->latest('id')
                ->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        if (! $this->isStaff($request)) {
            return $this->forbidden();
        }

        $validated = $request->validate($this->rules());
        $notice = Notice::create([
            ...$validated,
            'user_id' => $request->user()->id,
            'publish_date' => $validated['publish_date'] ?? now(),
        ]);

        return response()->json([
            'message' => 'Notice published successfully.',
            'data' => $notice->load('author:id,name'),
        ], 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        return response()->json([
            'data' => Notice::with('author:id,name')->findOrFail($id),
        ]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        if (! $this->isAdmin($request)) {
            return $this->forbidden();
        }

        $notice = Notice::findOrFail($id);
        $notice->update($request->validate($this->rules()));

        return response()->json([
            'message' => 'Notice updated successfully.',
            'data' => $notice->load('author:id,name'),
        ]);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        if (! $this->isAdmin($request)) {
            return $this->forbidden();
        }

        $notice = Notice::findOrFail($id);
        $notice->delete();

        return response()->json([
            'message' => 'Notice deleted successfully.',
        ]);
    }

    private function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'publish_date' => ['nullable', 'date'],
            'category' => ['required', Rule::in(['Academic', 'Academic Risk', 'Exam', 'Holiday', 'Meeting', 'Facility'])],
            'audience' => ['required', Rule::in(['All', 'Students', 'Faculty', 'Individual'])],
            'recipient_name' => ['nullable', 'required_if:audience,Individual', 'string', 'max:255'],
            'recipient_reference' => ['nullable', 'required_if:audience,Individual', 'string', 'max:255'],
        ];
    }

    private function isStaff(Request $request): bool
    {
        return in_array($request->user()->role, ['faculty', 'admin'], true);
    }

    private function isAdmin(Request $request): bool
    {
        return $request->user()->role === 'admin';
    }

    private function forbidden(): JsonResponse
    {
        return response()->json([
            'status' => false,
            'message' => 'You do not have permission to manage notices.',
        ], 403);
    }
}

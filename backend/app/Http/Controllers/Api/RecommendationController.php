<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Recommendation;
use App\Models\User;
use App\Services\CourseRecommendationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class RecommendationController extends Controller
{
    public function index(Request $request, CourseRecommendationService $service): JsonResponse
    {
        $user = $request->user();
        $query = Recommendation::with('course.faculty.user')->latest();

        if ($user->role !== 'admin') {
            $query->where('target_user_id', $user->id);
        }

        $created = $query->get();

        if ($created->isNotEmpty() || $user->role !== 'student') {
            return response()->json([
                'source' => 'advisor',
                'model' => null,
                'department' => $user->department,
                'message' => null,
                'data' => $created,
            ]);
        }

        return response()->json($service->forStudent($user));
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless(Gate::forUser($request->user())->allows('create', Recommendation::class), 403);

        $data = $request->validate($this->rules());
        $target = User::whereKey($data['target_user_id'])->where('role', 'student')->firstOrFail();
        $data['target_user'] = $target->name;
        $data['created_by_user_id'] = $request->user()->id;

        $recommendation = Recommendation::create($data)->load('course.faculty.user');

        return response()->json([
            'message' => 'Recommendation created successfully.',
            'data' => $recommendation,
        ], 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $recommendation = Recommendation::with('course.faculty.user')->findOrFail($id);
        abort_unless(Gate::forUser($request->user())->allows('view', $recommendation), 403);

        return response()->json(['data' => $recommendation]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $recommendation = Recommendation::findOrFail($id);
        abort_unless(Gate::forUser($request->user())->allows('update', $recommendation), 403);

        $data = $request->validate($this->rules());
        $target = User::whereKey($data['target_user_id'])->where('role', 'student')->firstOrFail();
        $data['target_user'] = $target->name;
        $recommendation->update($data);

        return response()->json([
            'message' => 'Recommendation updated successfully.',
            'data' => $recommendation->load('course.faculty.user'),
        ]);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $recommendation = Recommendation::findOrFail($id);
        abort_unless(Gate::forUser($request->user())->allows('delete', $recommendation), 403);
        $recommendation->delete();

        return response()->json(['message' => 'Recommendation deleted successfully.']);
    }

    private function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'recommendation_type' => ['required', 'string', 'max:255'],
            'target_user_id' => ['required', 'exists:users,id'],
            'course_id' => ['nullable', 'exists:courses,id'],
            'score' => ['nullable', 'numeric', 'between:0,100'],
        ];
    }
}

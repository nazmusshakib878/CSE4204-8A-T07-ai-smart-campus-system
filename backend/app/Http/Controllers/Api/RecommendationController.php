<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Recommendation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Throwable;

class RecommendationController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            return response()->json([
                'data' => Recommendation::latest()->get(),
            ], 200);
        } catch (Throwable $exception) {
            return $this->serverError('Unable to fetch recommendations.', $exception);
        }
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), $this->rules());

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $recommendation = Recommendation::create($validator->validated());

            return response()->json([
                'message' => 'Recommendation created successfully.',
                'data' => $recommendation,
            ], 201);
        } catch (Throwable $exception) {
            return $this->serverError('Unable to create recommendation.', $exception);
        }
    }

    public function show(string $id): JsonResponse
    {
        try {
            $recommendation = Recommendation::find($id);

            if (! $recommendation) {
                return $this->notFound('Recommendation not found.');
            }

            return response()->json([
                'data' => $recommendation,
            ], 200);
        } catch (Throwable $exception) {
            return $this->serverError('Unable to fetch recommendation.', $exception);
        }
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $validator = Validator::make($request->all(), $this->rules());

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $recommendation = Recommendation::find($id);

            if (! $recommendation) {
                return $this->notFound('Recommendation not found.');
            }

            $recommendation->update($validator->validated());

            return response()->json([
                'message' => 'Recommendation updated successfully.',
                'data' => $recommendation,
            ], 200);
        } catch (Throwable $exception) {
            return $this->serverError('Unable to update recommendation.', $exception);
        }
    }

    public function destroy(string $id): JsonResponse
    {
        try {
            $recommendation = Recommendation::find($id);

            if (! $recommendation) {
                return $this->notFound('Recommendation not found.');
            }

            $recommendation->delete();

            return response()->json([
                'message' => 'Recommendation deleted successfully.',
            ], 200);
        } catch (Throwable $exception) {
            return $this->serverError('Unable to delete recommendation.', $exception);
        }
    }

    private function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'recommendation_type' => ['required', 'string', 'max:255'],
            'target_user' => ['nullable', 'string', 'max:255'],
            'score' => ['nullable', 'numeric'],
        ];
    }

    private function validationError($errors): JsonResponse
    {
        return response()->json([
            'message' => 'Validation failed.',
            'errors' => $errors,
        ], 422);
    }

    private function notFound(string $message): JsonResponse
    {
        return response()->json([
            'message' => $message,
        ], 404);
    }

    private function serverError(string $message, Throwable $exception): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'error' => $exception->getMessage(),
        ], 500);
    }
}

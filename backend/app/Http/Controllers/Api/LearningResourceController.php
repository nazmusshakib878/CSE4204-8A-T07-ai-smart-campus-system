<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LearningResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Throwable;

class LearningResourceController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            $learningResources = LearningResource::latest()->get()->values();

            return response()->json([
                'message' => 'Learning resources retrieved successfully.',
                'data' => $learningResources->toArray(),
            ], 200);
        } catch (Throwable $exception) {
            return $this->serverError('Unable to fetch learning resources.', $exception);
        }
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), $this->rules());

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $validatedData = $validator->validated();

            $learningResource = new LearningResource;
            $learningResource->fill($validatedData);
            $learningResource->save();
            $learningResource->refresh();

            return response()->json([
                'message' => 'Learning resource created successfully.',
                'data' => $learningResource->toArray(),
            ], 201);
        } catch (Throwable $exception) {
            return $this->serverError('Unable to create learning resource.', $exception);
        }
    }

    public function show(string $id): JsonResponse
    {
        try {
            $learningResource = LearningResource::find($id);

            if (! $learningResource) {
                return $this->notFound('Learning resource not found.');
            }

            return response()->json([
                'data' => $learningResource,
            ], 200);
        } catch (Throwable $exception) {
            return $this->serverError('Unable to fetch learning resource.', $exception);
        }
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $validator = Validator::make($request->all(), $this->rules());

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $learningResource = LearningResource::find($id);

            if (! $learningResource) {
                return $this->notFound('Learning resource not found.');
            }

            $learningResource->update($validator->validated());

            return response()->json([
                'message' => 'Learning resource updated successfully.',
                'data' => $learningResource,
            ], 200);
        } catch (Throwable $exception) {
            return $this->serverError('Unable to update learning resource.', $exception);
        }
    }

    public function destroy(string $id): JsonResponse
    {
        try {
            $learningResource = LearningResource::find($id);

            if (! $learningResource) {
                return $this->notFound('Learning resource not found.');
            }

            $learningResource->delete();

            return response()->json([
                'message' => 'Learning resource deleted successfully.',
            ], 200);
        } catch (Throwable $exception) {
            return $this->serverError('Unable to delete learning resource.', $exception);
        }
    }

    private function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category' => ['required', 'string', 'max:255'],
            'resource_type' => ['required', 'string', 'max:255'],
            'resource_url' => ['nullable', 'string', 'max:2048'],
            'uploaded_by' => ['nullable', 'string', 'max:255'],
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

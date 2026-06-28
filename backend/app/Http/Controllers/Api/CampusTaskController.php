<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CampusTask;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Throwable;

class CampusTaskController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            return response()->json([
                'data' => CampusTask::latest()->get(),
            ], 200);
        } catch (Throwable $exception) {
            return $this->serverError('Unable to fetch tasks.', $exception);
        }
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), $this->rules());

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $task = CampusTask::create($validator->validated());

            return response()->json([
                'message' => 'Task created successfully.',
                'data' => $task,
            ], 201);
        } catch (Throwable $exception) {
            return $this->serverError('Unable to create task.', $exception);
        }
    }

    public function show(string $id): JsonResponse
    {
        try {
            $task = CampusTask::find($id);

            if (! $task) {
                return $this->notFound('Task not found.');
            }

            return response()->json([
                'data' => $task,
            ], 200);
        } catch (Throwable $exception) {
            return $this->serverError('Unable to fetch task.', $exception);
        }
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $validator = Validator::make($request->all(), $this->rules());

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $task = CampusTask::find($id);

            if (! $task) {
                return $this->notFound('Task not found.');
            }

            $task->update($validator->validated());

            return response()->json([
                'message' => 'Task updated successfully.',
                'data' => $task,
            ], 200);
        } catch (Throwable $exception) {
            return $this->serverError('Unable to update task.', $exception);
        }
    }

    public function destroy(string $id): JsonResponse
    {
        try {
            $task = CampusTask::find($id);

            if (! $task) {
                return $this->notFound('Task not found.');
            }

            $task->delete();

            return response()->json([
                'message' => 'Task deleted successfully.',
            ], 200);
        } catch (Throwable $exception) {
            return $this->serverError('Unable to delete task.', $exception);
        }
    }

    private function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'assigned_to' => ['nullable', 'string', 'max:255'],
            'due_date' => ['nullable', 'date'],
            'status' => ['nullable', Rule::in(['pending', 'in_progress', 'completed'])],
            'priority' => ['nullable', Rule::in(['low', 'medium', 'high'])],
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

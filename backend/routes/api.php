<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CampusTaskController;
use App\Http\Controllers\Api\DepartmentController;
use App\Http\Controllers\Api\LearningResourceController;
use App\Http\Controllers\Api\NoticeController;
use App\Http\Controllers\Api\RecommendationController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/departments', [DepartmentController::class, 'index']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/admin/create-admin', [AuthController::class, 'createAdmin']);
    Route::get('/admin/pending-users', [AuthController::class, 'pendingUsers']);
    Route::patch('/admin/users/{user}/approval', [AuthController::class, 'updateApprovalStatus']);
    Route::get('/admin/departments', [DepartmentController::class, 'adminIndex']);
    Route::post('/admin/departments', [DepartmentController::class, 'store']);
    Route::delete('/admin/departments/{department}', [DepartmentController::class, 'destroy']);

    Route::apiResource('learning-resources', LearningResourceController::class)
        ->parameters(['learning-resources' => 'id']);

    Route::apiResource('tasks', CampusTaskController::class)
        ->parameters(['tasks' => 'id']);

    Route::apiResource('recommendations', RecommendationController::class)
        ->parameters(['recommendations' => 'id']);

    Route::apiResource('notices', NoticeController::class)
        ->parameters(['notices' => 'id']);
});

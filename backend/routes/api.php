<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CampusTaskController;
use App\Http\Controllers\Api\LearningResourceController;
use App\Http\Controllers\Api\RecommendationController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::apiResource('learning-resources', LearningResourceController::class)
        ->parameters(['learning-resources' => 'id']);

    Route::apiResource('tasks', CampusTaskController::class)
        ->parameters(['tasks' => 'id']);

    Route::apiResource('recommendations', RecommendationController::class)
        ->parameters(['recommendations' => 'id']);
});

<?php

use App\Http\Controllers\Api\CampusTaskController;
use App\Http\Controllers\Api\LearningResourceController;
use App\Http\Controllers\Api\RecommendationController;
use Illuminate\Support\Facades\Route;

// Later: add auth middleware after authentication module is merged.
Route::apiResource('learning-resources', LearningResourceController::class)
    ->parameters(['learning-resources' => 'id']);
Route::apiResource('tasks', CampusTaskController::class)
    ->parameters(['tasks' => 'id']);
Route::apiResource('recommendations', RecommendationController::class)
    ->parameters(['recommendations' => 'id']);

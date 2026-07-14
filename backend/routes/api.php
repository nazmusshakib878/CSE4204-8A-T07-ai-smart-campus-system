<?php

use App\Http\Controllers\Api\AcademicManagementController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CampusTaskController;
use App\Http\Controllers\Api\DepartmentController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\LearningResourceController;
use App\Http\Controllers\Api\NoticeController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\RecommendationController;
use App\Http\Controllers\Api\StudentMonitoringController;
use App\Http\Controllers\Api\StudentDashboardController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/departments', [DepartmentController::class, 'index']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::put('/profile', [ProfileController::class, 'update']);
    Route::put('/profile/password', [ProfileController::class, 'password']);
    Route::post('/profile/photo', [ProfileController::class, 'uploadPhoto']);
    Route::delete('/profile/photo', [ProfileController::class, 'deletePhoto']);
    Route::get('/admin/dashboard', [DashboardController::class, 'admin']);
    Route::get('/faculty/dashboard', [DashboardController::class, 'faculty']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/admin/create-admin', [AuthController::class, 'createAdmin']);
    Route::get('/admin/pending-users', [AuthController::class, 'pendingUsers']);
    Route::patch('/admin/users/{user}/approval', [AuthController::class, 'updateApprovalStatus']);
    Route::get('/admin/departments', [DepartmentController::class, 'adminIndex']);
    Route::post('/admin/departments', [DepartmentController::class, 'store']);
    Route::delete('/admin/departments/{department}', [DepartmentController::class, 'destroy']);
    Route::get('/faculty/student-monitoring', [StudentMonitoringController::class, 'index']);
    Route::post('/faculty/students/{student}/analyze-risk', [StudentMonitoringController::class, 'analyze']);
    Route::get('/student/dashboard', [StudentDashboardController::class, 'show']);
    Route::post('/notices/{notice}/read', [NoticeController::class, 'markRead']);
    Route::patch('/notices/{notice}/archive', [NoticeController::class, 'archive']);
    Route::get('/notices/{notice}/attachment', [NoticeController::class, 'download']);

    Route::get('/academic-management', [AcademicManagementController::class, 'index']);
    Route::post('/academic-management/courses', [AcademicManagementController::class, 'storeCourse']);
    Route::put('/academic-management/courses/{course}', [AcademicManagementController::class, 'updateCourse']);
    Route::delete('/academic-management/courses/{course}', [AcademicManagementController::class, 'destroyCourse']);
    Route::get('/academic-management/courses/{course}/workspace', [AcademicManagementController::class, 'workspace']);
    Route::post('/academic-management/courses/{course}/enrollments', [AcademicManagementController::class, 'enroll']);
    Route::delete('/academic-management/courses/{course}/enrollments/{enrollment}', [AcademicManagementController::class, 'unenroll']);
    Route::put('/academic-management/courses/{course}/attendance', [AcademicManagementController::class, 'saveAttendance']);
    Route::put('/academic-management/courses/{course}/grades', [AcademicManagementController::class, 'saveGrades']);
    Route::put('/academic-management/courses/{course}/students/{student}/performance', [AcademicManagementController::class, 'savePerformance']);

    Route::apiResource('learning-resources', LearningResourceController::class)
        ->parameters(['learning-resources' => 'id']);

    Route::apiResource('tasks', CampusTaskController::class)
        ->parameters(['tasks' => 'id']);

    Route::apiResource('recommendations', RecommendationController::class)
        ->parameters(['recommendations' => 'id']);

    Route::apiResource('notices', NoticeController::class)
        ->parameters(['notices' => 'id']);
});

<?php

use App\Http\Controllers\Api\AcademicManagementController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AcademicExportController;
use App\Http\Controllers\Api\CampusOperationsController;
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

Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:auth');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:auth');
Route::get('/departments', [DepartmentController::class, 'index']);

Route::middleware(['auth:sanctum', 'approved', 'throttle:api'])->group(function () {
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
    Route::patch('/admin/departments/{department}/status', [DepartmentController::class, 'updateStatus']);
    Route::delete('/admin/departments/{department}', [DepartmentController::class, 'destroy']);
    Route::get('/faculty/student-monitoring', [StudentMonitoringController::class, 'index']);
    Route::post('/faculty/students/{student}/analyze-risk', [StudentMonitoringController::class, 'analyze']);
    Route::get('/student/dashboard', [StudentDashboardController::class, 'show']);
    Route::get('/student/transcript', [AcademicExportController::class, 'transcript']);
    Route::get('/student/attendance-export', [AcademicExportController::class, 'attendance']);
    Route::get('/students/{student}/transcript', [AcademicExportController::class, 'transcript']);
    Route::get('/students/{student}/attendance-export', [AcademicExportController::class, 'attendance']);
    Route::get('/campus-services', [CampusOperationsController::class, 'index']);
    Route::post('/campus-services/exams', [CampusOperationsController::class, 'storeExam']);
    Route::put('/campus-services/exams/{exam}', [CampusOperationsController::class, 'updateExam']);
    Route::delete('/campus-services/exams/{exam}', [CampusOperationsController::class, 'destroyExam']);
    Route::post('/campus-services/schedules', [CampusOperationsController::class, 'storeSchedule']);
    Route::put('/campus-services/schedules/{schedule}', [CampusOperationsController::class, 'updateSchedule']);
    Route::delete('/campus-services/schedules/{schedule}', [CampusOperationsController::class, 'destroySchedule']);
    Route::post('/campus-services/events', [CampusOperationsController::class, 'storeEvent']);
    Route::put('/campus-services/events/{event}', [CampusOperationsController::class, 'updateEvent']);
    Route::delete('/campus-services/events/{event}', [CampusOperationsController::class, 'destroyEvent']);
    Route::post('/campus-services/fees', [CampusOperationsController::class, 'storeFee']);
    Route::post('/campus-services/tickets', [CampusOperationsController::class, 'storeTicket']);
    Route::patch('/campus-services/tickets/{ticket}', [CampusOperationsController::class, 'updateTicket']);
    Route::post('/campus-services/leaves', [CampusOperationsController::class, 'storeLeave']);
    Route::patch('/campus-services/leaves/{leave}', [CampusOperationsController::class, 'reviewLeave']);
    Route::post('/campus-services/reschedules', [CampusOperationsController::class, 'storeReschedule']);
    Route::patch('/campus-services/reschedules/{reschedule}', [CampusOperationsController::class, 'reviewReschedule']);
    Route::post('/campus-services/books', [CampusOperationsController::class, 'storeBook']);
    Route::post('/campus-services/books/{book}/borrow', [CampusOperationsController::class, 'borrowBook']);
    Route::patch('/campus-services/loans/{loan}/return', [CampusOperationsController::class, 'returnLoan']);
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
    Route::put('/academic-management/courses/{course}/assessments', [AcademicManagementController::class, 'saveAssessments']);
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

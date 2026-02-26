<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Middleware\CheckAccessToken;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\WorklogController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\FCMController; 
use App\Http\Controllers\Api\AttendanceController; 
use App\Http\Controllers\Api\UserLinkController; 

Route::get('/test', function () {
    return response()->json(['ok' => true]);
});

Route::post('/login', [AuthController::class, 'login']);

Route::middleware([CheckAccessToken::class])->group(function () {
    Route::post('/user/store', [UserController::class, 'store']);
    Route::get('/user/list', [UserController::class, 'index']);
    Route::post('users/deactivate', [UserController::class, 'deactivateUser']);
    Route::post('users/activate', [UserController::class, 'activateUser']);
    Route::post('users/checkstatus', [UserController::class, 'checkUserStatus']);
    Route::post('/category/store', [CategoryController::class, 'store']);
    Route::get('/category/list', [CategoryController::class, 'index']);
    Route::post('/category/update/{id}', [CategoryController::class, 'update']);
    Route::post('/project/store', [ProjectController::class, 'store']);
    Route::post('/project/update-status', [ProjectController::class, 'updateStatus']);
    Route::get('/project/list', [ProjectController::class, 'index']);
    Route::post('/project/assign/user', [ProjectController::class, 'projectAssign']);
    Route::post('/project/assign/list/{id}', [ProjectController::class, 'projectAssignList']);
    Route::post('/worklogs/create', [WorklogController::class, 'store']);
    Route::get('/worklogs/user/{user_id}', [WorklogController::class, 'getByUser']);
    Route::post('/tasks/create', [TaskController::class, 'create']);
    Route::post('/tasks/update-status', [TaskController::class, 'updateStatus']);
    Route::get('/tasks/user/{user_id}', [TaskController::class, 'getUserTasks']);
    Route::post('tasks/by-user-project', [TaskController::class, 'tasksByUserAndProject']);
    Route::get('tasks/by-project/{projectId}', [TaskController::class, 'tasksByProject']);
    Route::post('/tasks/comment', [TaskController::class, 'addComment']);
    Route::get('/tasks/list', [TaskController::class, 'taskList']);
    Route::get('/tasks/todayTaskList', [TaskController::class, 'todayTaskList']);
    Route::get('/tasks/overdueTaskList', [TaskController::class, 'overdueTaskList']);
    Route::get('tasks/{taskId}', [TaskController::class, 'taskDetails']);
    Route::get('/notifications/user/{user_id}', [NotificationController::class, 'getUserNotifications']);
    Route::post('/notifications/read', [NotificationController::class, 'markAsRead']);
    Route::post('/save-fcm-token', [FCMController::class, 'saveToken']);
    // ATTENDANCE
    Route::post('/attendance/clockin', [AttendanceController::class, 'clockIn']);
    Route::post('/attendance/clockout', [AttendanceController::class, 'clockOut']);
    Route::get('/attendance/report/{user_id}', [AttendanceController::class, 'report']);
    Route::get('/attendance/today-status/{user_id}', [AttendanceController::class, 'todayStatus']);

    Route::post('/links/add',[UserLinkController::class,'store']);
    Route::get('/links/list',[UserLinkController::class,'list']);
    Route::post('/links/update/{id}',[UserLinkController::class,'update']);


});

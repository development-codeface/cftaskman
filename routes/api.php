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

Route::post('/login', [AuthController::class, 'login']);

Route::middleware([CheckAccessToken::class])->group(function () {
    Route::post('/user/store', [UserController::class, 'store']);
    Route::get('/user/list', [UserController::class, 'index']);
    Route::post('/category/store', [CategoryController::class, 'store']);
    Route::get('/category/list', [CategoryController::class, 'index']);
    Route::post('/project/store', [ProjectController::class, 'store']);
    Route::get('/project/list', [ProjectController::class, 'index']);
    Route::post('/project/assign/user', [ProjectController::class, 'projectAssign']);
    Route::post('/project/assign/list/{id}', [ProjectController::class, 'projectAssignList']);
    Route::post('/worklogs/create', [WorklogController::class, 'store']);
    Route::get('/worklogs/user/{user_id}', [WorklogController::class, 'getByUser']);
    Route::post('/tasks/create', [TaskController::class, 'create']);
    Route::post('/tasks/update-status', [TaskController::class, 'updateStatus']);
    Route::get('/tasks/user/{user_id}', [TaskController::class, 'getUserTasks']);
    Route::post('/tasks/comment', [TaskController::class, 'addComment']);
    Route::get('/notifications/user/{user_id}', [NotificationController::class, 'getUserNotifications']);
    Route::post('/notifications/read', [NotificationController::class, 'markAsRead']);
    Route::post('/save-fcm-token', [FCMController::class, 'saveToken']);

});

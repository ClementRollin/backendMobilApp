<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\InvitationCodeController;
use App\Http\Controllers\Api\TagController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\TaskLinkController;
use App\Http\Controllers\Api\TaskStatusHistoryController;
use App\Http\Controllers\Api\TeamController;
use App\Http\Controllers\Api\TeamMembershipController;
use App\Http\Controllers\Api\UserController;
use App\Support\ApiResponse;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => ApiResponse::success('TaskCollab API is running.', null));

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/users', [UserController::class, 'index']);

    Route::get('/tasks', [TaskController::class, 'index']);
    Route::post('/tasks', [TaskController::class, 'store']);
    Route::get('/tasks/{task}', [TaskController::class, 'show']);
    Route::put('/tasks/{task}', [TaskController::class, 'update']);
    Route::delete('/tasks/{task}', [TaskController::class, 'destroy']);
    Route::patch('/tasks/{task}/status', [TaskController::class, 'updateStatus']);
    Route::patch('/tasks/{task}/confirm-blocked', [TaskController::class, 'confirmBlocked']);
    Route::get('/tasks/{task}/status-histories', [TaskStatusHistoryController::class, 'index']);

    Route::get('/tasks/{task}/comments', [CommentController::class, 'index']);
    Route::post('/tasks/{task}/comments', [CommentController::class, 'store']);

    Route::get('/tasks/{task}/links', [TaskLinkController::class, 'index']);
    Route::post('/tasks/{task}/links', [TaskLinkController::class, 'store']);
    Route::delete('/task-links/{taskLink}', [TaskLinkController::class, 'destroy']);

    Route::get('/teams', [TeamController::class, 'index']);
    Route::post('/teams', [TeamController::class, 'store']);
    Route::get('/teams/{team}/memberships', [TeamMembershipController::class, 'index']);
    Route::post('/teams/{team}/memberships', [TeamMembershipController::class, 'store']);
    Route::delete('/teams/memberships/{teamMembership}', [TeamMembershipController::class, 'destroy']);

    Route::get('/invitation-codes', [InvitationCodeController::class, 'index']);
    Route::post('/invitation-codes', [InvitationCodeController::class, 'store']);
    Route::patch('/invitation-codes/{invitationCode}/revoke', [InvitationCodeController::class, 'revoke']);

    Route::get('/tags', [TagController::class, 'index']);
    Route::post('/tags', [TagController::class, 'store']);
    Route::put('/tags/{tag}', [TagController::class, 'update']);
    Route::delete('/tags/{tag}', [TagController::class, 'destroy']);
});

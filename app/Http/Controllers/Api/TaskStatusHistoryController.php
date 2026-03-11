<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TaskStatusHistoryResource;
use App\Models\Task;
use App\Models\TaskStatusHistory;
use App\Services\TaskStatusHistoryService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskStatusHistoryController extends Controller
{
    public function __construct(private readonly TaskStatusHistoryService $taskStatusHistoryService)
    {
    }

    public function index(Request $request, Task $task): JsonResponse
    {
        $this->authorize('viewAny', [TaskStatusHistory::class, $task]);

        $history = $this->taskStatusHistoryService->listForTask($task);

        return ApiResponse::success(
            'Task status history fetched successfully.',
            TaskStatusHistoryResource::collection($history)->resolve()
        );
    }
}


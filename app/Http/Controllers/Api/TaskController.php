<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Task\IndexTaskRequest;
use App\Http\Requests\Task\StoreTaskRequest;
use App\Http\Requests\Task\UpdateTaskRequest;
use App\Http\Requests\Task\UpdateTaskStatusRequest;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use App\Services\TaskService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function __construct(private readonly TaskService $taskService)
    {
    }

    public function index(IndexTaskRequest $request): JsonResponse
    {
        $paginator = $this->taskService->listVisibleTasks($request->user(), $request->validated());

        return ApiResponse::paginated('Tasks fetched successfully.', $paginator, static fn (Task $task) => TaskResource::make($task)->resolve());
    }

    public function store(StoreTaskRequest $request): JsonResponse
    {
        $task = $this->taskService->create($request->user(), $request->validated());

        return ApiResponse::success('Task created successfully.', TaskResource::make($task)->resolve(), 201);
    }

    public function show(Request $request, Task $task): JsonResponse
    {
        $this->authorize('view', $task);

        return ApiResponse::success(
            'Task fetched successfully.',
            TaskResource::make($task->load(['creator', 'assignee']))->resolve()
        );
    }

    public function update(UpdateTaskRequest $request, Task $task): JsonResponse
    {
        $this->authorize('update', $task);

        $updated = $this->taskService->update($task, $request->validated());

        return ApiResponse::success('Task updated successfully.', TaskResource::make($updated)->resolve());
    }

    public function destroy(Request $request, Task $task): JsonResponse
    {
        $this->authorize('delete', $task);

        $task->delete();

        return ApiResponse::success('Task deleted successfully.', null, 200);
    }

    public function updateStatus(UpdateTaskStatusRequest $request, Task $task): JsonResponse
    {
        $this->authorize('updateStatus', $task);

        $updated = $this->taskService->updateStatus($task, $request->validated()['status']);

        return ApiResponse::success('Task status updated successfully.', TaskResource::make($updated)->resolve());
    }
}

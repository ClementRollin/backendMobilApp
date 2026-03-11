<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\TaskLink\StoreTaskLinkRequest;
use App\Http\Resources\TaskLinkResource;
use App\Models\Task;
use App\Models\TaskLink;
use App\Services\TaskLinkService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskLinkController extends Controller
{
    public function __construct(private readonly TaskLinkService $taskLinkService)
    {
    }

    public function index(Request $request, Task $task): JsonResponse
    {
        $this->authorize('viewAny', [TaskLink::class, $task]);

        $links = $this->taskLinkService->listForTask($request->user(), $task);

        return ApiResponse::success('Task links fetched successfully.', TaskLinkResource::collection($links)->resolve());
    }

    public function store(StoreTaskLinkRequest $request, Task $task): JsonResponse
    {
        $this->authorize('create', [TaskLink::class, $task]);

        $linkedTask = Task::query()->findOrFail((int) $request->validated()['linked_task_id']);
        $link = $this->taskLinkService->create(
            $request->user(),
            $task,
            $linkedTask,
            $request->validated()['link_type'] ?? null
        );

        return ApiResponse::success('Task link created successfully.', TaskLinkResource::make($link)->resolve(), 201);
    }

    public function destroy(Request $request, TaskLink $taskLink): JsonResponse
    {
        $this->authorize('delete', $taskLink);

        $this->taskLinkService->delete($taskLink);

        return ApiResponse::success('Task link deleted successfully.', null);
    }
}


<?php

namespace App\Services;

use App\Models\Task;
use App\Models\TaskLink;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class TaskLinkService
{
    public function __construct(private readonly AccessService $accessService)
    {
    }

    public function listForTask(User $user, Task $task): Collection
    {
        if (! $this->accessService->canAccessTask($user, $task)) {
            throw new AuthorizationException('You are not allowed to view links for this task.');
        }

        return TaskLink::query()
            ->with(['lowTask.creator', 'lowTask.assignee', 'highTask.creator', 'highTask.assignee'])
            ->where('organization_id', $user->organization_id)
            ->where(function ($query) use ($task): void {
                $query->where('task_low_id', $task->id)
                    ->orWhere('task_high_id', $task->id);
            })
            ->orderBy('created_at')
            ->get();
    }

    public function create(User $user, Task $task, Task $linkedTask, ?string $linkType): TaskLink
    {
        if (! $this->accessService->canManageTask($user, $task)) {
            throw new AuthorizationException('You are not allowed to create links for this task.');
        }

        if (! $this->accessService->canAccessTask($user, $linkedTask)) {
            throw ValidationException::withMessages([
                'linked_task_id' => ['You cannot link to a task outside your allowed perimeter.'],
            ]);
        }

        $lowId = min((int) $task->id, (int) $linkedTask->id);
        $highId = max((int) $task->id, (int) $linkedTask->id);

        return TaskLink::query()->firstOrCreate(
            [
                'organization_id' => $user->organization_id,
                'task_low_id' => $lowId,
                'task_high_id' => $highId,
            ],
            [
                'link_type' => $linkType,
            ]
        )->load(['lowTask.creator', 'lowTask.assignee', 'highTask.creator', 'highTask.assignee']);
    }

    public function delete(TaskLink $taskLink): void
    {
        $taskLink->delete();
    }
}


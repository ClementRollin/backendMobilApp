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
            ->with(['lowTask.assignee', 'highTask.assignee'])
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

        if (! $this->accessService->canManageTask($user, $linkedTask)) {
            throw new AuthorizationException('You are not allowed to link this target task.');
        }

        $lowId = min((int) $task->id, (int) $linkedTask->id);
        $highId = max((int) $task->id, (int) $linkedTask->id);

        $alreadyExists = TaskLink::query()
            ->where('organization_id', $user->organization_id)
            ->where('task_low_id', $lowId)
            ->where('task_high_id', $highId)
            ->exists();

        if ($alreadyExists) {
            throw ValidationException::withMessages([
                'linked_task_id' => ['A link already exists between these tasks.'],
            ]);
        }

        return TaskLink::query()->create([
            'organization_id' => $user->organization_id,
            'task_low_id' => $lowId,
            'task_high_id' => $highId,
            'link_type' => $linkType,
        ])->load(['lowTask.assignee', 'highTask.assignee']);
    }

    public function delete(User $user, TaskLink $taskLink): void
    {
        $lowTask = $taskLink->lowTask;
        $highTask = $taskLink->highTask;

        if (! $lowTask || ! $highTask) {
            throw new AuthorizationException('Task link cannot be resolved.');
        }

        if (! $this->accessService->canManageTask($user, $lowTask)
            || ! $this->accessService->canManageTask($user, $highTask)) {
            throw new AuthorizationException('You are not allowed to delete this task link.');
        }

        $taskLink->delete();
    }
}

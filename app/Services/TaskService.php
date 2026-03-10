<?php

namespace App\Services;

use App\Models\Task;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class TaskService
{
    public function listVisibleTasks(User $user, array $filters): LengthAwarePaginator
    {
        $scope = $filters['scope'] ?? 'visible';
        $status = $filters['status'] ?? null;
        $perPage = (int) ($filters['per_page'] ?? 15);

        $query = Task::query()
            ->with(['creator', 'assignee'])
            ->orderByDesc('created_at');

        $query = $this->applyScope($query, $user, $scope);

        if ($status !== null) {
            $query->where('status', $status);
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function create(User $user, array $payload): Task
    {
        return Task::query()->create([
            'creator_id' => $user->id,
            'assignee_id' => $payload['assignee_id'] ?? null,
            'title' => $payload['title'],
            'description' => $payload['description'] ?? null,
            'status' => $payload['status'],
            'priority' => $payload['priority'],
            'due_date' => $payload['due_date'] ?? null,
        ])->load(['creator', 'assignee']);
    }

    public function update(Task $task, array $payload): Task
    {
        $task->fill([
            'assignee_id' => $payload['assignee_id'] ?? null,
            'title' => $payload['title'],
            'description' => $payload['description'] ?? null,
            'status' => $payload['status'],
            'priority' => $payload['priority'],
            'due_date' => $payload['due_date'] ?? null,
        ])->save();

        return $task->load(['creator', 'assignee']);
    }

    public function updateStatus(Task $task, string $status): Task
    {
        $task->status = $status;
        $task->save();

        return $task->load(['creator', 'assignee']);
    }

    private function applyScope(Builder $query, User $user, string $scope): Builder
    {
        return match ($scope) {
            'created' => $query->where('creator_id', $user->id),
            'assigned' => $query->where('assignee_id', $user->id),
            default => $query->where(function (Builder $builder) use ($user) {
                $builder->where('creator_id', $user->id)
                    ->orWhere('assignee_id', $user->id);
            }),
        };
    }
}

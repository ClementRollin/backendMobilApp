<?php

namespace App\Services;

use App\Enums\TaskStatus;
use App\Enums\UserRole;
use App\Models\Task;
use App\Models\TaskLink;
use App\Models\TaskStatusHistory;
use App\Models\TeamMembership;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TaskService
{
    public function __construct(private readonly AccessService $accessService)
    {
    }

    private function roleValue(User $user): string
    {
        return $user->role instanceof UserRole
            ? $user->role->value
            : (string) $user->role;
    }

    public function listVisibleTasks(User $user, array $filters): LengthAwarePaginator
    {
        $scope = (string) ($filters['scope'] ?? request()->query('scope', 'visible'));
        $status = $filters['status'] ?? null;
        $priority = $filters['priority'] ?? null;
        $teamId = isset($filters['team_id']) ? (int) $filters['team_id'] : null;
        $assigneeId = isset($filters['assignee_id']) ? (int) $filters['assignee_id'] : null;
        $creatorId = isset($filters['creator_id']) ? (int) $filters['creator_id'] : null;
        $dueBefore = $filters['due_before'] ?? null;
        $dueAfter = $filters['due_after'] ?? null;
        $search = trim((string) ($filters['search'] ?? ''));
        $tagIds = $filters['tag_ids'] ?? [];
        $perPage = max(1, min(50, (int) ($filters['per_page'] ?? 15)));

        if ($scope === 'unassigned' && ! $this->accessService->isLead($user)) {
            throw new AuthorizationException('This scope is only available for lead users.');
        }

        $query = Task::query()
            ->with(['creator', 'assignee', 'team', 'blockedConfirmedBy', 'tags'])
            ->orderByDesc('created_at');

        $query = $this->accessService->applyTaskScope($query, $user, $scope);

        if ($status !== null) {
            $query->where('status', $status);
        }

        if ($priority !== null) {
            $query->where('priority', $priority);
        }

        if ($teamId !== null) {
            $query->where('team_id', $teamId);
        }

        if ($assigneeId !== null) {
            $query->where('assignee_id', $assigneeId);
        }

        if ($creatorId !== null) {
            $query->where('creator_id', $creatorId);
        }

        if ($dueBefore !== null) {
            $query->where('due_date', '<=', $dueBefore);
        }

        if ($dueAfter !== null) {
            $query->where('due_date', '>=', $dueAfter);
        }

        if ($search !== '') {
            $needle = '%'.mb_strtolower($search).'%';
            $query->where(static function (Builder $builder) use ($needle): void {
                $builder->whereRaw('LOWER(title) LIKE ?', [$needle])
                    ->orWhereRaw("LOWER(COALESCE(description, '')) LIKE ?", [$needle]);
            });
        }

        if (is_array($tagIds) && $tagIds !== []) {
            $query->whereHas('tags', static function (Builder $builder) use ($tagIds): void {
                $builder->whereIn('tags.id', $tagIds);
            });
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function create(User $user, array $payload): Task
    {
        if (! $this->accessService->canCreateTaskInTeam($user, (int) $payload['team_id'])) {
            throw new AuthorizationException('You are not allowed to create tasks for this team.');
        }

        $this->validateAssigneeConsistency($user, (int) $payload['team_id'], $payload['assignee_id'] ?? null);

        return DB::transaction(function () use ($user, $payload): Task {
            $task = Task::query()->create([
                'organization_id' => $user->organization_id,
                'team_id' => $payload['team_id'],
                'creator_id' => $user->id,
                'assignee_id' => $payload['assignee_id'] ?? null,
                'title' => $payload['title'],
                'description' => $payload['description'] ?? null,
                'status' => $payload['status'],
                'priority' => $payload['priority'],
                'blocked_reason' => $payload['blocked_reason'] ?? null,
                'due_date' => $payload['due_date'] ?? null,
                'deployed_at' => $payload['status'] === TaskStatus::DEPLOYED->value ? now() : null,
            ]);

            if (array_key_exists('tag_ids', $payload) && is_array($payload['tag_ids'])) {
                $task->tags()->sync($payload['tag_ids']);
            }

            $this->addStatusHistory(
                task: $task,
                userId: $user->id,
                oldStatus: null,
                newStatus: $payload['status'],
                comment: 'Task created.',
                metadata: ['source' => 'create']
            );

            return $task->load(['creator', 'assignee', 'team', 'blockedConfirmedBy', 'tags']);
        });
    }

    public function update(User $user, Task $task, array $payload): Task
    {
        if (! $this->accessService->canManageTask($user, $task)) {
            throw new AuthorizationException('You are not allowed to update this task.');
        }

        if (! $this->accessService->isLeadOfTeam($user, (int) $payload['team_id'])) {
            throw new AuthorizationException('You are not allowed to move this task to this team.');
        }

        $this->validateAssigneeConsistency($user, (int) $payload['team_id'], $payload['assignee_id'] ?? null);

        return DB::transaction(function () use ($user, $task, $payload): Task {
            $task->fill([
                'team_id' => $payload['team_id'],
                'assignee_id' => $payload['assignee_id'] ?? null,
                'title' => $payload['title'],
                'description' => $payload['description'] ?? null,
                'priority' => $payload['priority'],
                'blocked_reason' => $payload['blocked_reason'] ?? $task->blocked_reason,
                'due_date' => $payload['due_date'] ?? null,
            ])->save();

            if (array_key_exists('tag_ids', $payload) && is_array($payload['tag_ids'])) {
                $task->tags()->sync($payload['tag_ids']);
            }

            return $task->load(['creator', 'assignee', 'team', 'blockedConfirmedBy', 'tags']);
        });
    }

    public function updateStatus(User $user, Task $task, array $payload): Task
    {
        if (! $this->accessService->canAccessTask($user, $task)) {
            throw new AuthorizationException('You are not allowed to update this task status.');
        }

        if ($this->roleValue($user) === UserRole::CTO->value) {
            throw new AuthorizationException('CTO cannot update task statuses.');
        }

        return DB::transaction(function () use ($user, $task, $payload): Task {
            $task->refresh();
            $fromStatus = $this->statusValue($task->status);
            $toStatus = $payload['status'];

            if (! $this->isTransitionAllowed($user, $fromStatus, $toStatus)) {
                throw ValidationException::withMessages([
                    'status' => ["Transition {$fromStatus} -> {$toStatus} is not allowed for this role."],
                ]);
            }

            if ($toStatus === TaskStatus::BLOCKED->value && empty($payload['blocked_reason'])) {
                throw ValidationException::withMessages([
                    'blocked_reason' => ['Blocked reason is required when moving to blocked.'],
                ]);
            }

            $task->status = $toStatus;
            if ($toStatus === TaskStatus::BLOCKED->value) {
                $task->blocked_reason = $payload['blocked_reason'];
            }
            if ($toStatus === TaskStatus::DEPLOYED->value) {
                $task->deployed_at = $task->deployed_at ?? now();
            }
            $task->save();

            $this->addStatusHistory(
                task: $task,
                userId: $user->id,
                oldStatus: $fromStatus,
                newStatus: $toStatus,
                comment: $payload['comment'] ?? null,
                metadata: $payload['metadata'] ?? null
            );

            return $task->load(['creator', 'assignee', 'team', 'blockedConfirmedBy', 'tags']);
        });
    }

    public function confirmBlocked(User $user, Task $task, array $payload): Task
    {
        if (! $this->accessService->canManageTask($user, $task)) {
            throw new AuthorizationException('You are not allowed to confirm blocked for this task.');
        }

        return DB::transaction(function () use ($user, $task, $payload): Task {
            $task->refresh();

            if ($this->statusValue($task->status) !== TaskStatus::BLOCKED->value) {
                throw ValidationException::withMessages([
                    'status' => ['Only blocked tasks can be confirmed.'],
                ]);
            }

            if ($task->blocked_confirmed_at !== null) {
                throw ValidationException::withMessages([
                    'blocked_confirmed_at' => ['Task blocked status is already confirmed.'],
                ]);
            }

            $task->blocked_confirmed_at = now();
            $task->blocked_confirmed_by = $user->id;
            $task->save();

            $metadata = array_merge(
                ['blocked_confirmed' => true],
                is_array($payload['metadata'] ?? null) ? $payload['metadata'] : []
            );

            $this->addStatusHistory(
                task: $task,
                userId: $user->id,
                oldStatus: TaskStatus::BLOCKED->value,
                newStatus: TaskStatus::BLOCKED->value,
                comment: $payload['comment'] ?? 'Blocked status confirmed by lead.',
                metadata: $metadata
            );

            return $task->load(['creator', 'assignee', 'team', 'blockedConfirmedBy', 'tags']);
        });
    }

    public function delete(User $user, Task $task): void
    {
        if (! $this->accessService->canManageTask($user, $task)) {
            throw new AuthorizationException('You are not allowed to delete this task.');
        }

        DB::transaction(function () use ($task): void {
            $task->tags()->detach();

            TaskLink::query()
                ->where('task_low_id', $task->id)
                ->orWhere('task_high_id', $task->id)
                ->delete();

            TaskStatusHistory::query()
                ->where('task_id', $task->id)
                ->delete();

            $task->comments()->delete();
            $task->delete();
        });
    }

    private function isTransitionAllowed(User $user, string $fromStatus, string $toStatus): bool
    {
        $map = match ($this->roleValue($user)) {
            UserRole::DEVELOPER->value => [
                TaskStatus::TODO->value => [TaskStatus::IN_PROGRESS->value],
                TaskStatus::IN_PROGRESS->value => [TaskStatus::BLOCKED->value, TaskStatus::IN_REVIEW->value],
            ],
            UserRole::LEAD_DEV->value => [
                TaskStatus::TODO->value => [TaskStatus::IN_PROGRESS->value],
                TaskStatus::IN_PROGRESS->value => [TaskStatus::BLOCKED->value],
                TaskStatus::BLOCKED->value => [TaskStatus::IN_PROGRESS->value],
                TaskStatus::IN_REVIEW->value => [TaskStatus::WAITING_FOR_TEST->value, TaskStatus::IN_PROGRESS->value],
                TaskStatus::TESTED->value => [TaskStatus::DEPLOYED->value],
            ],
            UserRole::PO->value => [
                TaskStatus::WAITING_FOR_TEST->value => [TaskStatus::TESTED->value, TaskStatus::IN_PROGRESS->value],
            ],
            default => [],
        };

        return in_array($toStatus, $map[$fromStatus] ?? [], true);
    }

    private function addStatusHistory(
        Task $task,
        int $userId,
        ?string $oldStatus,
        string $newStatus,
        ?string $comment = null,
        ?array $metadata = null
    ): TaskStatusHistory {
        return TaskStatusHistory::query()->create([
            'organization_id' => $task->organization_id,
            'task_id' => $task->id,
            'user_id' => $userId,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'comment' => $comment,
            'metadata' => $metadata,
        ]);
    }

    private function statusValue(TaskStatus|string|null $status): string
    {
        if ($status instanceof TaskStatus) {
            return $status->value;
        }

        return (string) $status;
    }

    private function validateAssigneeConsistency(User $actor, int $teamId, mixed $assigneeId): void
    {
        if ($assigneeId === null) {
            return;
        }

        $assignee = User::query()->find((int) $assigneeId);
        if (! $assignee || (int) $assignee->organization_id !== (int) $actor->organization_id) {
            throw ValidationException::withMessages([
                'assignee_id' => ['The selected assignee is not in your organization.'],
            ]);
        }

        $isTeamMember = TeamMembership::query()
            ->where('organization_id', $actor->organization_id)
            ->where('team_id', $teamId)
            ->where('user_id', $assignee->id)
            ->exists();
        if (! $isTeamMember) {
            throw ValidationException::withMessages([
                'assignee_id' => ['The selected assignee is not a member of this team.'],
            ]);
        }

        $assigneeRole = $this->roleValue($assignee);
        $isSelfAssignment = (int) $assignee->id === (int) $actor->id;
        if (! $isSelfAssignment && $assigneeRole !== UserRole::DEVELOPER->value) {
            throw ValidationException::withMessages([
                'assignee_id' => ['Assignee must be a developer or yourself.'],
            ]);
        }

        if ($assigneeRole === UserRole::DEVELOPER->value) {
            $membershipCount = TeamMembership::query()
                ->where('organization_id', $actor->organization_id)
                ->where('user_id', $assignee->id)
                ->count();

            if ($membershipCount > 1) {
                throw ValidationException::withMessages([
                    'assignee_id' => ['The selected developer belongs to multiple teams.'],
                ]);
            }
        }
    }
}

<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\TaskLink;
use App\Models\User;
use App\Services\AccessService;

class TaskLinkPolicy
{
    public function __construct(private readonly AccessService $accessService)
    {
    }

    public function viewAny(User $user, Task $task): bool
    {
        return $this->accessService->canAccessTask($user, $task);
    }

    public function create(User $user, Task $task): bool
    {
        return $this->accessService->canManageTask($user, $task);
    }

    public function delete(User $user, TaskLink $taskLink): bool
    {
        if ((int) $taskLink->organization_id !== (int) $user->organization_id) {
            return false;
        }

        if (! $this->accessService->isLead($user)) {
            return false;
        }

        $task = $taskLink->lowTask;
        if ($task === null) {
            return false;
        }

        return $this->accessService->canManageTask($user, $task);
    }
}


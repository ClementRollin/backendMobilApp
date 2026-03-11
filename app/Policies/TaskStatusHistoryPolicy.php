<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\TaskStatusHistory;
use App\Models\User;
use App\Services\AccessService;

class TaskStatusHistoryPolicy
{
    public function __construct(private readonly AccessService $accessService)
    {
    }

    public function viewAny(User $user, Task $task): bool
    {
        return $this->accessService->canAccessTask($user, $task);
    }

    public function view(User $user, TaskStatusHistory $history): bool
    {
        return $history->task !== null
            && $this->accessService->canAccessTask($user, $history->task);
    }
}


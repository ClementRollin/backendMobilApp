<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;
use App\Services\AccessService;

class TaskPolicy
{
    public function __construct(private readonly AccessService $accessService)
    {
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Task $task): bool
    {
        return $this->accessService->canAccessTask($user, $task);
    }

    public function create(User $user): bool
    {
        return $this->accessService->isLead($user);
    }

    public function update(User $user, Task $task): bool
    {
        return $this->accessService->canManageTask($user, $task);
    }

    public function delete(User $user, Task $task): bool
    {
        return $this->accessService->canManageTask($user, $task);
    }

    public function updateStatus(User $user, Task $task): bool
    {
        if (! $this->accessService->canAccessTask($user, $task)) {
            return false;
        }

        return ! $this->accessService->isCto($user);
    }

    public function confirmBlocked(User $user, Task $task): bool
    {
        return $this->accessService->canManageTask($user, $task);
    }

    public function comment(User $user, Task $task): bool
    {
        return $this->accessService->canAccessTask($user, $task);
    }
}

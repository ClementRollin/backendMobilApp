<?php

namespace App\Services;

use App\Models\Task;
use Illuminate\Database\Eloquent\Collection;

class TaskStatusHistoryService
{
    public function listForTask(Task $task): Collection
    {
        return $task->statusHistories()
            ->with('user')
            ->orderByDesc('created_at')
            ->get();
    }
}


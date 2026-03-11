<?php

namespace Database\Seeders;

use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\TaskStatusHistory;
use App\Models\User;
use Illuminate\Database\Seeder;

class TaskStatusHistorySeeder extends Seeder
{
    public function run(): void
    {
        TaskStatusHistory::query()->delete();

        $lead = User::query()->where('email', 'lead@technova.fr')->firstOrFail();
        $developer = User::query()->where('email', 'dev@technova.fr')->firstOrFail();
        $blockedTask = Task::query()->where('title', 'Stabiliser la campagne de tests')->firstOrFail();

        TaskStatusHistory::query()->create([
            'organization_id' => $blockedTask->organization_id,
            'task_id' => $blockedTask->id,
            'user_id' => $developer->id,
            'old_status' => TaskStatus::IN_PROGRESS->value,
            'new_status' => TaskStatus::BLOCKED->value,
            'comment' => 'Blocage sur un endpoint tiers instable.',
            'metadata' => [
                'blocked_reason' => $blockedTask->blocked_reason,
                'origin' => 'seed',
            ],
        ]);

        TaskStatusHistory::query()->create([
            'organization_id' => $blockedTask->organization_id,
            'task_id' => $blockedTask->id,
            'user_id' => $lead->id,
            'old_status' => TaskStatus::BLOCKED->value,
            'new_status' => TaskStatus::BLOCKED->value,
            'comment' => 'Blocage confirme par le lead.',
            'metadata' => [
                'blocked_confirmed' => true,
            ],
        ]);
    }
}


<?php

namespace Database\Seeders;

use App\Models\Task;
use App\Models\TaskLink;
use Illuminate\Database\Seeder;

class TaskLinkSeeder extends Seeder
{
    public function run(): void
    {
        TaskLink::query()->delete();

        $apiTask = Task::query()->where('title', 'Finaliser API Auth')->firstOrFail();
        $kanbanTask = Task::query()->where('title', 'Mettre en place le Kanban mobile')->firstOrFail();

        $lowId = min($apiTask->id, $kanbanTask->id);
        $highId = max($apiTask->id, $kanbanTask->id);

        TaskLink::query()->create([
            'organization_id' => $apiTask->organization_id,
            'task_low_id' => $lowId,
            'task_high_id' => $highId,
            'link_type' => 'depends_on',
        ]);
    }
}


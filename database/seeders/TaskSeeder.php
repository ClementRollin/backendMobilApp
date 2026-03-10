<?php

namespace Database\Seeders;

use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class TaskSeeder extends Seeder
{
    public function run(): void
    {
        $alice = User::query()->where('email', 'alice@example.com')->firstOrFail();
        $bob = User::query()->where('email', 'bob@example.com')->firstOrFail();
        $chloe = User::query()->where('email', 'chloe@example.com')->firstOrFail();

        Task::query()->delete();

        Task::query()->create([
            'creator_id' => $alice->id,
            'assignee_id' => $bob->id,
            'title' => 'Préparer la soutenance mobile',
            'description' => 'Structurer les slides et scénario de démonstration.',
            'status' => 'in_progress',
            'priority' => 'high',
            'due_date' => Carbon::now()->addHours(10)->toIso8601String(),
        ]);

        Task::query()->create([
            'creator_id' => $alice->id,
            'assignee_id' => null,
            'title' => 'Refactor écran détail tâche',
            'description' => 'Améliorer la lisibilité des badges et sections.',
            'status' => 'todo',
            'priority' => 'medium',
            'due_date' => Carbon::now()->addDays(2)->toIso8601String(),
        ]);

        Task::query()->create([
            'creator_id' => $bob->id,
            'assignee_id' => $alice->id,
            'title' => 'Configurer PostgreSQL sur la VM',
            'description' => 'Créer la base task_collab et vérifier les migrations.',
            'status' => 'done',
            'priority' => 'low',
            'due_date' => Carbon::now()->subDay()->toIso8601String(),
        ]);

        Task::query()->create([
            'creator_id' => $chloe->id,
            'assignee_id' => $bob->id,
            'title' => 'Tester les endpoints API',
            'description' => 'Vérifier les codes HTTP et le contrat JSON uniforme.',
            'status' => 'todo',
            'priority' => 'high',
            'due_date' => Carbon::now()->addHours(20)->toIso8601String(),
        ]);
    }
}

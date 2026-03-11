<?php

namespace Database\Seeders;

use App\Models\Comment;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;

class CommentSeeder extends Seeder
{
    public function run(): void
    {
        $lead = User::query()->where('email', 'lead@technova.fr')->firstOrFail();
        $developer = User::query()->where('email', 'dev@technova.fr')->firstOrFail();
        $po = User::query()->where('email', 'po@technova.fr')->firstOrFail();
        $blockedTask = Task::query()->where('title', 'Stabiliser la campagne de tests')->firstOrFail();
        $reviewTask = Task::query()->where('title', 'Finaliser API Auth')->firstOrFail();

        Comment::query()->delete();

        Comment::query()->create([
            'task_id' => $reviewTask->id,
            'user_id' => $developer->id,
            'content' => 'Le correctif OAuth est pret pour revue.',
        ]);

        Comment::query()->create([
            'task_id' => $blockedTask->id,
            'user_id' => $developer->id,
            'content' => 'Blocage confirme, service externe hors ligne.',
        ]);

        Comment::query()->create([
            'task_id' => $blockedTask->id,
            'user_id' => $lead->id,
            'content' => 'J analyse le plan de contournement avec le PO.',
        ]);

        Comment::query()->create([
            'task_id' => $reviewTask->id,
            'user_id' => $po->id,
            'content' => 'Scenario de validation fonctionnelle en preparation.',
        ]);
    }
}


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
        $alice = User::query()->where('email', 'alice@example.com')->firstOrFail();
        $bob = User::query()->where('email', 'bob@example.com')->firstOrFail();
        $chloe = User::query()->where('email', 'chloe@example.com')->firstOrFail();

        Comment::query()->delete();

        $taskA = Task::query()->where('title', 'Préparer la soutenance mobile')->firstOrFail();
        $taskB = Task::query()->where('title', 'Tester les endpoints API')->firstOrFail();

        Comment::query()->create([
            'task_id' => $taskA->id,
            'user_id' => $alice->id,
            'content' => 'Je prépare le script de présentation cette après-midi.',
        ]);

        Comment::query()->create([
            'task_id' => $taskA->id,
            'user_id' => $bob->id,
            'content' => 'Je prends les captures d’écran de l’app mobile.',
        ]);

        Comment::query()->create([
            'task_id' => $taskB->id,
            'user_id' => $chloe->id,
            'content' => 'Les retours 422 sont conformes pour les validations.',
        ]);
    }
}

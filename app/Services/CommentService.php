<?php

namespace App\Services;

use App\Models\Comment;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class CommentService
{
    public function listForTask(Task $task): Collection
    {
        return $task->comments()
            ->with('user')
            ->orderBy('created_at')
            ->get();
    }

    public function create(Task $task, User $user, string $content): Comment
    {
        return Comment::query()->create([
            'task_id' => $task->id,
            'user_id' => $user->id,
            'content' => $content,
        ])->load('user');
    }
}

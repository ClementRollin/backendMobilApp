<?php

namespace Tests\Feature;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Comment;
use App\Models\Task;
use App\Models\TaskLink;
use App\Models\TaskStatusHistory;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TaskDeleteConsistencyFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_task_delete_cleans_related_records_without_orphans(): void
    {
        $lead = User::query()->where('email', 'lead@technova.fr')->firstOrFail();
        $developer = User::query()->where('email', 'dev@technova.fr')->firstOrFail();
        $team = Team::query()->where('name', 'Equipe API')->firstOrFail();
        $linkedTask = Task::query()->where('title', 'Finaliser API Auth')->firstOrFail();

        $task = Task::query()->create([
            'organization_id' => $lead->organization_id,
            'team_id' => $team->id,
            'creator_id' => $lead->id,
            'assignee_id' => $developer->id,
            'title' => 'Task cleanup cible',
            'description' => 'Task pour verifier le nettoyage.',
            'status' => TaskStatus::TODO->value,
            'priority' => TaskPriority::MEDIUM->value,
        ]);
        $task->tags()->sync($linkedTask->tags()->pluck('tags.id')->all());

        $comment = Comment::query()->create([
            'task_id' => $task->id,
            'user_id' => $lead->id,
            'content' => 'Commentaire test cleanup',
        ]);
        $history = TaskStatusHistory::query()->create([
            'organization_id' => $lead->organization_id,
            'task_id' => $task->id,
            'user_id' => $lead->id,
            'old_status' => TaskStatus::TODO->value,
            'new_status' => TaskStatus::IN_PROGRESS->value,
            'comment' => 'Historique test cleanup',
            'metadata' => ['source' => 'test'],
        ]);

        $lowId = min($task->id, $linkedTask->id);
        $highId = max($task->id, $linkedTask->id);
        $taskLink = TaskLink::query()->create([
            'organization_id' => $lead->organization_id,
            'task_low_id' => $lowId,
            'task_high_id' => $highId,
            'link_type' => 'depends_on',
        ]);

        $response = $this->deleteJson("/api/tasks/{$task->id}", [], $this->authHeaders('lead@technova.fr'));
        $response->assertOk();
        $response->assertJsonPath('data', null);

        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
        $this->assertDatabaseMissing('task_status_histories', ['id' => $history->id]);
        $this->assertDatabaseMissing('task_links', ['id' => $taskLink->id]);
        $this->assertSame(0, DB::table('task_tag')->where('task_id', $task->id)->count());
    }

    private function authHeaders(string $email): array
    {
        $user = User::query()->where('email', $email)->firstOrFail();
        $token = $user->createToken('test-token')->plainTextToken;

        return [
            'Authorization' => "Bearer {$token}",
            'Accept' => 'application/json',
        ];
    }
}


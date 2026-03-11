<?php

namespace Tests\Feature;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskStatusLockFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_put_task_rejects_status_modification(): void
    {
        $task = Task::query()->where('title', 'Finaliser API Auth')->firstOrFail();

        $response = $this->putJson("/api/tasks/{$task->id}", [
            'team_id' => $task->team_id,
            'title' => $task->title,
            'description' => $task->description,
            'assignee_id' => $task->assignee_id,
            'priority' => TaskPriority::HIGH->value,
            'due_date' => optional($task->due_date)->toIso8601String(),
            'status' => TaskStatus::DEPLOYED->value,
            'tag_ids' => $task->tags()->pluck('tags.id')->all(),
        ], $this->authHeaders('lead@technova.fr'));

        $response->assertStatus(422);
        $response->assertJsonPath('errors.status.0', 'Status updates must use PATCH /api/tasks/{task}/status.');
    }

    public function test_status_transition_endpoint_remains_the_only_supported_path(): void
    {
        $task = Task::query()->where('title', 'Mettre en place le Kanban mobile')->firstOrFail();

        $response = $this->patchJson("/api/tasks/{$task->id}/status", [
            'status' => TaskStatus::IN_PROGRESS->value,
        ], $this->authHeaders('lead@technova.fr'));

        $response->assertOk();
        $response->assertJsonPath('data.status', TaskStatus::IN_PROGRESS->value);
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


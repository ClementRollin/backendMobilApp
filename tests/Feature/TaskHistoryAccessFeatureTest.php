<?php

namespace Tests\Feature;

use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskHistoryAccessFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_status_history_is_forbidden_without_task_access(): void
    {
        $task = Task::query()->where('title', 'Mettre en place le Kanban mobile')->firstOrFail();

        $response = $this->getJson("/api/tasks/{$task->id}/status-histories", $this->authHeaders('dev@technova.fr'));
        $response->assertStatus(403);
    }

    public function test_status_history_is_sorted_desc_and_has_minimal_format(): void
    {
        $task = Task::query()->where('title', 'Stabiliser la campagne de tests')->firstOrFail();

        $this->patchJson("/api/tasks/{$task->id}/status", [
            'status' => TaskStatus::IN_PROGRESS->value,
            'comment' => 'Deblocage de test',
        ], $this->authHeaders('lead@technova.fr'))->assertOk();

        $response = $this->getJson("/api/tasks/{$task->id}/status-histories", $this->authHeaders('lead@technova.fr'));
        $response->assertOk();

        $first = $response->json('data.0');
        $this->assertArrayHasKey('id', $first);
        $this->assertArrayHasKey('user', $first);
        $this->assertArrayHasKey('old_status', $first);
        $this->assertArrayHasKey('new_status', $first);
        $this->assertArrayHasKey('comment', $first);
        $this->assertArrayHasKey('metadata', $first);
        $this->assertArrayHasKey('created_at', $first);

        $timestamps = collect($response->json('data'))->pluck('created_at')->filter()->values()->all();
        $sorted = $timestamps;
        rsort($sorted);
        $this->assertSame($sorted, $timestamps);
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


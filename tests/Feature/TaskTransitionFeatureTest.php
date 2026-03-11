<?php

namespace Tests\Feature;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskTransitionFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_developer_cannot_apply_forbidden_transition(): void
    {
        $developer = User::query()->where('email', 'dev@technova.fr')->firstOrFail();
        $team = Team::query()->where('name', 'Equipe API')->firstOrFail();

        $task = Task::query()->create([
            'organization_id' => $developer->organization_id,
            'team_id' => $team->id,
            'creator_id' => User::query()->where('email', 'lead@technova.fr')->value('id'),
            'assignee_id' => $developer->id,
            'title' => 'Transition interdit test',
            'description' => null,
            'status' => TaskStatus::BLOCKED->value,
            'priority' => TaskPriority::MEDIUM->value,
            'blocked_reason' => 'Blocked for test.',
        ]);

        $response = $this->patchJson("/api/tasks/{$task->id}/status", [
            'status' => TaskStatus::IN_PROGRESS->value,
        ], $this->authHeaders('dev@technova.fr'));

        $response->assertStatus(422);
    }

    public function test_po_can_move_waiting_for_test_to_tested(): void
    {
        $po = User::query()->where('email', 'po@technova.fr')->firstOrFail();
        $team = Team::query()->where('name', 'Equipe Mobile')->firstOrFail();
        $lead = User::query()->where('email', 'lead@technova.fr')->firstOrFail();

        $task = Task::query()->create([
            'organization_id' => $po->organization_id,
            'team_id' => $team->id,
            'creator_id' => $lead->id,
            'assignee_id' => null,
            'title' => 'Validation PO',
            'description' => null,
            'status' => TaskStatus::WAITING_FOR_TEST->value,
            'priority' => TaskPriority::MEDIUM->value,
        ]);

        $response = $this->patchJson("/api/tasks/{$task->id}/status", [
            'status' => TaskStatus::TESTED->value,
        ], $this->authHeaders('po@technova.fr'));

        $response->assertOk();
        $response->assertJsonPath('data.status', TaskStatus::TESTED->value);
    }

    public function test_lead_can_move_tested_to_deployed(): void
    {
        $lead = User::query()->where('email', 'lead@technova.fr')->firstOrFail();
        $team = Team::query()->where('name', 'Equipe API')->firstOrFail();

        $task = Task::query()->create([
            'organization_id' => $lead->organization_id,
            'team_id' => $team->id,
            'creator_id' => $lead->id,
            'assignee_id' => User::query()->where('email', 'dev@technova.fr')->value('id'),
            'title' => 'Deployment path',
            'description' => null,
            'status' => TaskStatus::TESTED->value,
            'priority' => TaskPriority::HIGH->value,
        ]);

        $response = $this->patchJson("/api/tasks/{$task->id}/status", [
            'status' => TaskStatus::DEPLOYED->value,
        ], $this->authHeaders('lead@technova.fr'));

        $response->assertOk();
        $response->assertJsonPath('data.status', TaskStatus::DEPLOYED->value);
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


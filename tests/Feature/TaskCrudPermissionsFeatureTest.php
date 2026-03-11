<?php

namespace Tests\Feature;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Enums\UserRole;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TaskCrudPermissionsFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_lead_can_create_task_in_managed_team(): void
    {
        $lead = User::query()->where('email', 'lead@technova.fr')->firstOrFail();
        $apiTeam = Team::query()->where('name', 'Equipe API')->firstOrFail();
        $developer = User::query()->where('email', 'dev@technova.fr')->firstOrFail();

        $response = $this->postJson('/api/tasks', [
            'team_id' => $apiTeam->id,
            'title' => 'Nouvelle tache API',
            'description' => 'Description de test.',
            'assignee_id' => $developer->id,
            'status' => TaskStatus::TODO->value,
            'priority' => TaskPriority::MEDIUM->value,
            'due_date' => now()->addDay()->toIso8601String(),
        ], $this->authHeaders('lead@technova.fr'));

        $response->assertStatus(201);
        $response->assertJsonPath('data.creator_id', $lead->id);
        $response->assertJsonPath('data.team_id', $apiTeam->id);
    }

    public function test_lead_cannot_create_task_in_unmanaged_team(): void
    {
        $lead = User::query()->where('email', 'lead@technova.fr')->firstOrFail();
        $unmanagedTeam = Team::query()->create([
            'organization_id' => $lead->organization_id,
            'name' => 'Equipe Infra',
            'description' => 'Equipe non geree par le lead.',
        ]);

        $response = $this->postJson('/api/tasks', [
            'team_id' => $unmanagedTeam->id,
            'title' => 'Creation interdite',
            'description' => null,
            'assignee_id' => null,
            'status' => TaskStatus::TODO->value,
            'priority' => TaskPriority::LOW->value,
        ], $this->authHeaders('lead@technova.fr'));

        $response->assertStatus(403);
    }

    public function test_developer_and_cto_cannot_create_tasks(): void
    {
        $apiTeam = Team::query()->where('name', 'Equipe API')->firstOrFail();
        $developer = User::query()->where('email', 'dev@technova.fr')->firstOrFail();

        foreach (['dev@technova.fr', 'cto@technova.fr'] as $email) {
            $response = $this->postJson('/api/tasks', [
                'team_id' => $apiTeam->id,
                'title' => 'Creation non autorisee',
                'description' => 'test',
                'assignee_id' => $developer->id,
                'status' => TaskStatus::TODO->value,
                'priority' => TaskPriority::LOW->value,
            ], $this->authHeaders($email));

            $response->assertStatus(403);
        }
    }

    public function test_developer_cannot_update_task(): void
    {
        $task = Task::query()->where('title', 'Finaliser API Auth')->firstOrFail();
        $developerHeaders = $this->authHeaders('dev@technova.fr');

        $forbiddenUpdate = $this->putJson("/api/tasks/{$task->id}", [
            'team_id' => $task->team_id,
            'title' => 'Modification interdite',
            'description' => $task->description,
            'assignee_id' => $task->assignee_id,
            'priority' => TaskPriority::HIGH->value,
            'due_date' => optional($task->due_date)->toIso8601String(),
            'tag_ids' => $task->tags()->pluck('tags.id')->all(),
        ], $developerHeaders);
        $forbiddenUpdate->assertStatus(403);
    }

    public function test_lead_can_delete_task_and_receive_data_null(): void
    {
        $task = Task::query()->where('title', 'Finaliser API Auth')->firstOrFail();

        $deleteResponse = $this->deleteJson("/api/tasks/{$task->id}", [], $this->authHeaders('lead@technova.fr'));
        $deleteResponse->assertOk();
        $deleteResponse->assertJsonPath('data', null);
    }

    public function test_lead_cannot_update_task_outside_managed_scope(): void
    {
        $organizationId = User::query()->where('email', 'lead@technova.fr')->value('organization_id');
        $otherLead = User::query()->create([
            'organization_id' => $organizationId,
            'role' => UserRole::LEAD_DEV->value,
            'first_name' => 'Autre',
            'last_name' => 'Lead',
            'name' => 'Autre Lead',
            'email' => 'autre.lead@technova.fr',
            'password' => Hash::make('password123'),
        ]);
        $otherTeam = Team::query()->create([
            'organization_id' => $organizationId,
            'name' => 'Equipe Web',
            'description' => null,
        ]);
        $task = Task::query()->create([
            'organization_id' => $organizationId,
            'team_id' => $otherTeam->id,
            'creator_id' => $otherLead->id,
            'assignee_id' => null,
            'title' => 'Task hors perimetre lead',
            'description' => null,
            'status' => TaskStatus::TODO->value,
            'priority' => TaskPriority::LOW->value,
        ]);

        $response = $this->putJson("/api/tasks/{$task->id}", [
            'team_id' => $otherTeam->id,
            'title' => 'Update hors perimetre',
            'description' => null,
            'assignee_id' => null,
            'priority' => TaskPriority::MEDIUM->value,
            'due_date' => null,
        ], $this->authHeaders('lead@technova.fr'));

        $response->assertStatus(403);
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

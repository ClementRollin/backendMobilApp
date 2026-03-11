<?php

namespace Tests\Feature;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Enums\UserRole;
use App\Models\Organization;
use App\Models\Task;
use App\Models\Team;
use App\Models\TeamMembership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TaskAssignmentFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_lead_can_assign_task_to_team_developer(): void
    {
        $lead = User::query()->where('email', 'lead@technova.fr')->firstOrFail();
        $developer = User::query()->where('email', 'dev@technova.fr')->firstOrFail();
        $team = Team::query()->where('name', 'Equipe API')->firstOrFail();
        $task = Task::query()->create([
            'organization_id' => $lead->organization_id,
            'team_id' => $team->id,
            'creator_id' => $lead->id,
            'assignee_id' => null,
            'title' => 'A assigner',
            'description' => null,
            'status' => TaskStatus::TODO->value,
            'priority' => TaskPriority::LOW->value,
        ]);

        $response = $this->putJson("/api/tasks/{$task->id}", [
            'team_id' => $team->id,
            'title' => $task->title,
            'description' => $task->description,
            'assignee_id' => $developer->id,
            'priority' => TaskPriority::MEDIUM->value,
            'due_date' => now()->addDay()->toIso8601String(),
            'tag_ids' => [],
        ], $this->authHeaders('lead@technova.fr'));

        $response->assertOk();
        $response->assertJsonPath('data.assignee_id', $developer->id);
    }

    public function test_lead_can_self_assign_task(): void
    {
        $lead = User::query()->where('email', 'lead@technova.fr')->firstOrFail();
        $task = Task::query()->where('title', 'Finaliser API Auth')->firstOrFail();

        $response = $this->putJson("/api/tasks/{$task->id}", [
            'team_id' => $task->team_id,
            'title' => $task->title,
            'description' => $task->description,
            'assignee_id' => $lead->id,
            'priority' => $task->priority instanceof TaskPriority ? $task->priority->value : (string) $task->priority,
            'due_date' => optional($task->due_date)->toIso8601String(),
            'tag_ids' => $task->tags()->pluck('tags.id')->all(),
        ], $this->authHeaders('lead@technova.fr'));

        $response->assertOk();
        $response->assertJsonPath('data.assignee_id', $lead->id);
    }

    public function test_assignment_to_non_eligible_role_is_rejected(): void
    {
        $po = User::query()->where('email', 'po@technova.fr')->firstOrFail();
        $task = Task::query()->where('title', 'Finaliser API Auth')->firstOrFail();

        $response = $this->putJson("/api/tasks/{$task->id}", [
            'team_id' => $task->team_id,
            'title' => $task->title,
            'description' => $task->description,
            'assignee_id' => $po->id,
            'priority' => TaskPriority::HIGH->value,
            'due_date' => optional($task->due_date)->toIso8601String(),
            'tag_ids' => $task->tags()->pluck('tags.id')->all(),
        ], $this->authHeaders('lead@technova.fr'));

        $response->assertStatus(422);
    }

    public function test_assignment_cross_organization_is_rejected(): void
    {
        $organization = Organization::query()->create([
            'name' => 'Autre Organisation',
            'slug' => 'autre-organisation',
        ]);
        $foreignDeveloper = User::query()->create([
            'organization_id' => $organization->id,
            'role' => UserRole::DEVELOPER->value,
            'first_name' => 'Dev',
            'last_name' => 'Externe',
            'name' => 'Dev Externe',
            'email' => 'dev.externe@org.fr',
            'password' => Hash::make('password123'),
        ]);

        $task = Task::query()->where('title', 'Finaliser API Auth')->firstOrFail();

        $response = $this->putJson("/api/tasks/{$task->id}", [
            'team_id' => $task->team_id,
            'title' => $task->title,
            'description' => $task->description,
            'assignee_id' => $foreignDeveloper->id,
            'priority' => TaskPriority::HIGH->value,
            'due_date' => optional($task->due_date)->toIso8601String(),
            'tag_ids' => $task->tags()->pluck('tags.id')->all(),
        ], $this->authHeaders('lead@technova.fr'));

        $response->assertStatus(422);
    }

    public function test_unassign_is_supported_with_nullable_assignee(): void
    {
        $task = Task::query()->where('title', 'Finaliser API Auth')->firstOrFail();

        $response = $this->putJson("/api/tasks/{$task->id}", [
            'team_id' => $task->team_id,
            'title' => $task->title,
            'description' => $task->description,
            'assignee_id' => null,
            'priority' => TaskPriority::MEDIUM->value,
            'due_date' => optional($task->due_date)->toIso8601String(),
            'tag_ids' => $task->tags()->pluck('tags.id')->all(),
        ], $this->authHeaders('lead@technova.fr'));

        $response->assertOk();
        $response->assertJsonPath('data.assignee_id', null);
    }

    public function test_assignment_rejects_developer_with_multiple_team_memberships(): void
    {
        $developer = User::query()->where('email', 'dev@technova.fr')->firstOrFail();
        $lead = User::query()->where('email', 'lead@technova.fr')->firstOrFail();
        $mobileTeam = Team::query()->where('name', 'Equipe Mobile')->firstOrFail();
        TeamMembership::query()->create([
            'organization_id' => $lead->organization_id,
            'team_id' => $mobileTeam->id,
            'user_id' => $developer->id,
        ]);

        $task = Task::query()->where('title', 'Finaliser API Auth')->firstOrFail();
        $response = $this->putJson("/api/tasks/{$task->id}", [
            'team_id' => $task->team_id,
            'title' => $task->title,
            'description' => $task->description,
            'assignee_id' => $developer->id,
            'priority' => TaskPriority::HIGH->value,
            'due_date' => optional($task->due_date)->toIso8601String(),
            'tag_ids' => $task->tags()->pluck('tags.id')->all(),
        ], $this->authHeaders('lead@technova.fr'));

        $response->assertStatus(422);
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


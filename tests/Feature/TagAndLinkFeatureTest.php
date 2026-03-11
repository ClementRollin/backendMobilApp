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

class TagAndLinkFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_developer_cannot_create_tags(): void
    {
        $developerResponse = $this->postJson('/api/tags', [
            'name' => 'security',
        ], $this->authHeaders('dev@technova.fr'));

        $developerResponse->assertStatus(403);
    }

    public function test_lead_can_create_tags(): void
    {
        $leadResponse = $this->postJson('/api/tags', [
            'name' => 'security',
            'color' => '#334155',
        ], $this->authHeaders('lead@technova.fr'));

        $leadResponse->assertStatus(201);
    }

    public function test_task_link_creation_rejects_cross_organization_tasks(): void
    {
        $lead = User::query()->where('email', 'lead@technova.fr')->firstOrFail();
        $baseTask = Task::query()->where('title', 'Finaliser API Auth')->firstOrFail();

        $otherOrganization = Organization::query()->create([
            'name' => 'OtherOrg',
            'slug' => 'other-org',
        ]);

        $otherLead = User::query()->create([
            'organization_id' => $otherOrganization->id,
            'role' => UserRole::LEAD_DEV->value,
            'first_name' => 'Other',
            'last_name' => 'Lead',
            'name' => 'Other Lead',
            'email' => 'lead@other-org.fr',
            'password' => Hash::make('password123'),
        ]);
        $otherTeam = Team::query()->create([
            'organization_id' => $otherOrganization->id,
            'name' => 'Other Team',
            'description' => null,
        ]);
        TeamMembership::query()->create([
            'organization_id' => $otherOrganization->id,
            'team_id' => $otherTeam->id,
            'user_id' => $otherLead->id,
        ]);

        $otherTask = Task::query()->create([
            'organization_id' => $otherOrganization->id,
            'team_id' => $otherTeam->id,
            'creator_id' => $otherLead->id,
            'assignee_id' => null,
            'title' => 'Cross org task',
            'description' => null,
            'status' => TaskStatus::TODO->value,
            'priority' => TaskPriority::LOW->value,
        ]);

        $response = $this->postJson("/api/tasks/{$baseTask->id}/links", [
            'linked_task_id' => $otherTask->id,
            'link_type' => 'depends_on',
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

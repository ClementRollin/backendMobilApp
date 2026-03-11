<?php

namespace Tests\Feature;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Enums\UserRole;
use App\Models\Organization;
use App\Models\Tag;
use App\Models\Task;
use App\Models\TaskLink;
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

    public function test_lead_can_update_and_delete_tag(): void
    {
        $tag = $this->postJson('/api/tags', [
            'name' => 'performance',
            'color' => '#0EA5E9',
        ], $this->authHeaders('lead@technova.fr'))->json('data');

        $updateResponse = $this->putJson("/api/tags/{$tag['id']}", [
            'name' => 'performance-api',
            'color' => '#0284C7',
        ], $this->authHeaders('lead@technova.fr'));
        $updateResponse->assertOk();

        $deleteResponse = $this->deleteJson("/api/tags/{$tag['id']}", [], $this->authHeaders('lead@technova.fr'));
        $deleteResponse->assertOk();
    }

    public function test_cto_cannot_update_tags(): void
    {
        $organizationId = User::query()->where('email', 'lead@technova.fr')->value('organization_id');
        $tag = Tag::query()->create([
            'organization_id' => $organizationId,
            'name' => 'cto-po-denied',
            'color' => '#334155',
        ]);

        $ctoUpdate = $this->putJson("/api/tags/{$tag->id}", [
            'name' => 'cto-update',
            'color' => '#111827',
        ], $this->authHeaders('cto@technova.fr'));
        $ctoUpdate->assertStatus(403);
    }

    public function test_po_cannot_delete_tags(): void
    {
        $organizationId = User::query()->where('email', 'lead@technova.fr')->value('organization_id');
        $tag = Tag::query()->create([
            'organization_id' => $organizationId,
            'name' => 'po-denied',
            'color' => '#1E293B',
        ]);

        $poDelete = $this->deleteJson("/api/tags/{$tag->id}", [], $this->authHeaders('po@technova.fr'));
        $poDelete->assertStatus(403);
    }

    public function test_task_create_rejects_cross_organization_tag(): void
    {
        $lead = User::query()->where('email', 'lead@technova.fr')->firstOrFail();
        $apiTeam = Team::query()->where('name', 'Equipe API')->firstOrFail();
        $otherOrganization = Organization::query()->create([
            'name' => 'Org Tags Externe',
            'slug' => 'org-tags-externe',
        ]);
        $foreignTag = Tag::query()->create([
            'organization_id' => $otherOrganization->id,
            'name' => 'foreign-tag',
            'color' => '#111827',
        ]);

        $response = $this->postJson('/api/tasks', [
            'team_id' => $apiTeam->id,
            'title' => 'Task avec tag externe',
            'description' => null,
            'assignee_id' => null,
            'status' => TaskStatus::TODO->value,
            'priority' => TaskPriority::LOW->value,
            'tag_ids' => [$foreignTag->id],
        ], $this->authHeaders('lead@technova.fr'));

        $response->assertStatus(422);
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

        $response->assertStatus(403);
    }

    public function test_lead_can_create_and_delete_task_link(): void
    {
        $lead = User::query()->where('email', 'lead@technova.fr')->firstOrFail();
        $apiTeam = Team::query()->where('name', 'Equipe API')->firstOrFail();

        $leftTask = Task::query()->create([
            'organization_id' => $lead->organization_id,
            'team_id' => $apiTeam->id,
            'creator_id' => $lead->id,
            'assignee_id' => User::query()->where('email', 'dev@technova.fr')->value('id'),
            'title' => 'Task link left',
            'description' => null,
            'status' => TaskStatus::TODO->value,
            'priority' => TaskPriority::LOW->value,
        ]);
        $rightTask = Task::query()->create([
            'organization_id' => $lead->organization_id,
            'team_id' => $apiTeam->id,
            'creator_id' => $lead->id,
            'assignee_id' => null,
            'title' => 'Task link right',
            'description' => null,
            'status' => TaskStatus::IN_PROGRESS->value,
            'priority' => TaskPriority::MEDIUM->value,
        ]);

        $createResponse = $this->postJson("/api/tasks/{$leftTask->id}/links", [
            'linked_task_id' => $rightTask->id,
            'link_type' => 'relates_to',
        ], $this->authHeaders('lead@technova.fr'));
        $createResponse->assertStatus(201);
        $createResponse->assertJsonPath('data.linked_task.id', $rightTask->id);
        $this->assertNotNull($createResponse->json('data.linked_task.status'));

        $linkId = $createResponse->json('data.id');
        $deleteResponse = $this->deleteJson("/api/task-links/{$linkId}", [], $this->authHeaders('lead@technova.fr'));
        $deleteResponse->assertOk();
    }

    public function test_dev_po_cto_cannot_create_links(): void
    {
        $baseTask = Task::query()->where('title', 'Finaliser API Auth')->firstOrFail();
        $linkedTask = Task::query()->where('title', 'Stabiliser la campagne de tests')->firstOrFail();

        foreach (['dev@technova.fr', 'po@technova.fr', 'cto@technova.fr'] as $email) {
            $response = $this->postJson("/api/tasks/{$baseTask->id}/links", [
                'linked_task_id' => $linkedTask->id,
                'link_type' => 'depends_on',
            ], $this->authHeaders($email));
            $response->assertStatus(403);
        }
    }

    public function test_auto_link_and_duplicate_link_are_rejected(): void
    {
        $task = Task::query()->where('title', 'Finaliser API Auth')->firstOrFail();

        $autoLinkResponse = $this->postJson("/api/tasks/{$task->id}/links", [
            'linked_task_id' => $task->id,
            'link_type' => 'depends_on',
        ], $this->authHeaders('lead@technova.fr'));
        $autoLinkResponse->assertStatus(422);

        $existingLink = TaskLink::query()->firstOrFail();
        $sourceId = $existingLink->task_low_id;
        $targetId = $existingLink->task_high_id;
        $duplicateResponse = $this->postJson("/api/tasks/{$sourceId}/links", [
            'linked_task_id' => $targetId,
            'link_type' => 'depends_on',
        ], $this->authHeaders('lead@technova.fr'));
        $duplicateResponse->assertStatus(422);
    }

    public function test_link_requires_lead_access_on_source_and_linked_tasks(): void
    {
        $organizationId = User::query()->where('email', 'lead@technova.fr')->value('organization_id');
        $otherLead = User::query()->create([
            'organization_id' => $organizationId,
            'role' => UserRole::LEAD_DEV->value,
            'first_name' => 'Second',
            'last_name' => 'Lead',
            'name' => 'Second Lead',
            'email' => 'second.lead@technova.fr',
            'password' => Hash::make('password123'),
        ]);
        $isolatedTeam = Team::query()->create([
            'organization_id' => $organizationId,
            'name' => 'Equipe Isolee',
            'description' => null,
        ]);
        TeamMembership::query()->create([
            'organization_id' => $organizationId,
            'team_id' => $isolatedTeam->id,
            'user_id' => $otherLead->id,
        ]);
        $isolatedTask = Task::query()->create([
            'organization_id' => $organizationId,
            'team_id' => $isolatedTeam->id,
            'creator_id' => $otherLead->id,
            'assignee_id' => null,
            'title' => 'Task isolée',
            'description' => null,
            'status' => TaskStatus::TODO->value,
            'priority' => TaskPriority::LOW->value,
        ]);

        $sourceTask = Task::query()->where('title', 'Finaliser API Auth')->firstOrFail();
        $response = $this->postJson("/api/tasks/{$sourceTask->id}/links", [
            'linked_task_id' => $isolatedTask->id,
            'link_type' => 'depends_on',
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

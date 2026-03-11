<?php

namespace Tests\Feature;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Enums\UserRole;
use App\Models\Organization;
use App\Models\Tag;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TaskFiltersFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_default_per_page_is_15_and_upper_bound_is_50(): void
    {
        $defaultResponse = $this->getJson('/api/tasks', $this->authHeaders('lead@technova.fr'));
        $defaultResponse->assertOk();
        $defaultResponse->assertJsonPath('meta.per_page', 15);

        $tooHighResponse = $this->getJson('/api/tasks?per_page=51', $this->authHeaders('lead@technova.fr'));
        $tooHighResponse->assertStatus(422);
    }

    public function test_filters_status_priority_team_assignee_and_creator(): void
    {
        $task = Task::query()->where('title', 'Finaliser API Auth')->firstOrFail();

        $response = $this->getJson(
            "/api/tasks?status=in_review&priority=high&team_id={$task->team_id}&assignee_id={$task->assignee_id}&creator_id={$task->creator_id}",
            $this->authHeaders('lead@technova.fr')
        );

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $response->assertJsonPath('data.0.id', $task->id);
    }

    public function test_due_date_filters_are_inclusive(): void
    {
        $lead = User::query()->where('email', 'lead@technova.fr')->firstOrFail();
        $team = Team::query()->where('name', 'Equipe API')->firstOrFail();
        $boundary = now()->addDays(5)->startOfMinute();

        $task = Task::query()->create([
            'organization_id' => $lead->organization_id,
            'team_id' => $team->id,
            'creator_id' => $lead->id,
            'assignee_id' => null,
            'title' => 'Boundary due date',
            'description' => null,
            'status' => TaskStatus::TODO->value,
            'priority' => TaskPriority::LOW->value,
            'due_date' => $boundary->toIso8601String(),
        ]);

        $formatted = urlencode($boundary->toIso8601String());
        $responseBefore = $this->getJson("/api/tasks?due_before={$formatted}", $this->authHeaders('lead@technova.fr'));
        $responseBefore->assertOk();
        $this->assertTrue(collect($responseBefore->json('data'))->contains(fn (array $item) => (int) $item['id'] === (int) $task->id));

        $responseAfter = $this->getJson("/api/tasks?due_after={$formatted}", $this->authHeaders('lead@technova.fr'));
        $responseAfter->assertOk();
        $this->assertTrue(collect($responseAfter->json('data'))->contains(fn (array $item) => (int) $item['id'] === (int) $task->id));
    }

    public function test_search_is_case_insensitive_trimmed_and_limited_to_title_and_description(): void
    {
        $response = $this->getJson('/api/tasks?search=%20%20API%20AUTH%20%20', $this->authHeaders('lead@technova.fr'));
        $response->assertOk();
        $this->assertTrue(collect($response->json('data'))->contains(fn (array $item) => $item['title'] === 'Finaliser API Auth'));
    }

    public function test_tag_filter_uses_or_semantics(): void
    {
        $bugTag = Tag::query()->where('name', 'bug')->firstOrFail();
        $mobileTag = Tag::query()->where('name', 'mobile')->firstOrFail();

        $response = $this->getJson(
            "/api/tasks?tag_ids[]={$bugTag->id}&tag_ids[]={$mobileTag->id}",
            $this->authHeaders('lead@technova.fr')
        );

        $response->assertOk();
        $titles = collect($response->json('data'))->pluck('title')->all();
        $this->assertContains('Finaliser API Auth', $titles);
        $this->assertContains('Mettre en place le Kanban mobile', $titles);
    }

    public function test_creator_filter_rejects_other_organization_creator(): void
    {
        $otherOrganization = Organization::query()->create([
            'name' => 'Autre org',
            'slug' => 'autre-org-filters',
        ]);
        $otherUser = User::query()->create([
            'organization_id' => $otherOrganization->id,
            'role' => UserRole::LEAD_DEV->value,
            'first_name' => 'Lead',
            'last_name' => 'Externe',
            'name' => 'Lead Externe',
            'email' => 'lead.externe.filters@org.fr',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->getJson("/api/tasks?creator_id={$otherUser->id}", $this->authHeaders('lead@technova.fr'));
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


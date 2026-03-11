<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskScopeFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_unassigned_scope_returns_data_for_lead(): void
    {
        $leadHeaders = $this->authHeaders('lead@technova.fr');
        $leadResponse = $this->getJson('/api/tasks?scope=unassigned', $leadHeaders);
        $leadResponse->assertOk();
    }

    public function test_unassigned_scope_is_forbidden_for_developer(): void
    {
        $developerHeaders = $this->authHeaders('dev@technova.fr');
        $developerResponse = $this->getJson('/api/tasks?scope=unassigned', $developerHeaders);
        $developerResponse->assertStatus(403);
    }

    public function test_cto_visible_scope_returns_all_organization_tasks_including_unassigned(): void
    {
        $headers = $this->authHeaders('cto@technova.fr');
        $expectedCount = Task::query()->count();

        $response = $this->getJson('/api/tasks?scope=visible', $headers);

        $response->assertOk();
        $this->assertCount($expectedCount, $response->json('data'));
        $this->assertTrue(collect($response->json('data'))->contains(fn (array $task) => $task['assignee_id'] === null));
    }

    public function test_created_scope_returns_empty_for_roles_that_do_not_create_tasks(): void
    {
        foreach (['cto@technova.fr', 'dev@technova.fr', 'po@technova.fr'] as $email) {
            $response = $this->getJson('/api/tasks?scope=created', $this->authHeaders($email));
            $response->assertOk();
            $this->assertSame([], $response->json('data'));
        }
    }

    private function authHeaders(string $email): array
    {
        $user = User::query()->where('email', $email)->firstOrFail();
        $this->assertContains($user->role, UserRole::cases());
        $token = $user->createToken('test-token')->plainTextToken;

        return [
            'Authorization' => "Bearer {$token}",
            'Accept' => 'application/json',
        ];
    }
}

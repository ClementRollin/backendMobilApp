<?php

namespace Tests\Feature;

use App\Enums\TaskStatus;
use App\Enums\UserRole;
use App\Models\InvitationCode;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BackendWorkflowFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_cto_visible_scope_includes_unassigned_tasks(): void
    {
        $headers = $this->authHeaders('cto@technova.fr');

        $response = $this->getJson('/api/tasks?scope=visible', $headers);

        $response->assertOk();
        $this->assertTrue(collect($response->json('data'))->contains(fn (array $task) => $task['assignee_id'] === null));
    }

    public function test_comment_is_forbidden_when_user_has_no_access_to_task(): void
    {
        $headers = $this->authHeaders('dev@technova.fr');
        $unassignedMobileTask = Task::query()->where('title', 'Mettre en place le Kanban mobile')->firstOrFail();

        $response = $this->postJson("/api/tasks/{$unassignedMobileTask->id}/comments", [
            'content' => 'Je commente sans acces.',
        ], $headers);

        $response->assertStatus(403);
    }

    public function test_scope_created_returns_empty_for_developer(): void
    {
        $headers = $this->authHeaders('dev@technova.fr');

        $response = $this->getJson('/api/tasks?scope=created', $headers);

        $response->assertOk();
        $this->assertSame([], $response->json('data'));
    }

    public function test_lead_can_move_task_from_todo_to_in_progress(): void
    {
        $headers = $this->authHeaders('lead@technova.fr');
        $task = Task::query()->where('status', TaskStatus::TODO->value)->firstOrFail();

        $response = $this->patchJson("/api/tasks/{$task->id}/status", [
            'status' => TaskStatus::IN_PROGRESS->value,
        ], $headers);

        $response->assertOk();
        $response->assertJsonPath('data.status', TaskStatus::IN_PROGRESS->value);
    }

    public function test_confirm_blocked_rejects_non_blocked_and_already_confirmed_tasks(): void
    {
        $headers = $this->authHeaders('lead@technova.fr');
        $nonBlockedTask = Task::query()->where('status', TaskStatus::IN_REVIEW->value)->firstOrFail();

        $responseNonBlocked = $this->patchJson("/api/tasks/{$nonBlockedTask->id}/confirm-blocked", [], $headers);
        $responseNonBlocked->assertStatus(422);

        $alreadyConfirmedTask = Task::query()->where('status', TaskStatus::BLOCKED->value)->firstOrFail();
        $this->assertNotNull($alreadyConfirmedTask->blocked_confirmed_at);

        $responseAlreadyConfirmed = $this->patchJson("/api/tasks/{$alreadyConfirmedTask->id}/confirm-blocked", [], $headers);
        $responseAlreadyConfirmed->assertStatus(422);
    }

    public function test_status_history_response_contains_required_fields(): void
    {
        $headers = $this->authHeaders('lead@technova.fr');
        $task = Task::query()->where('title', 'Stabiliser la campagne de tests')->firstOrFail();

        $response = $this->getJson("/api/tasks/{$task->id}/status-histories", $headers);

        $response->assertOk();
        $first = $response->json('data.0');
        $this->assertArrayHasKey('id', $first);
        $this->assertArrayHasKey('user', $first);
        $this->assertArrayHasKey('old_status', $first);
        $this->assertArrayHasKey('new_status', $first);
        $this->assertArrayHasKey('comment', $first);
        $this->assertArrayHasKey('metadata', $first);
        $this->assertArrayHasKey('created_at', $first);
    }

    public function test_lead_can_create_invitation_only_for_own_lead_teams(): void
    {
        $lead = User::query()->where('email', 'lead@technova.fr')->firstOrFail();
        $otherTeam = Team::query()->create([
            'organization_id' => $lead->organization_id,
            'name' => 'Equipe Externe',
            'description' => 'Equipe non geree par le lead de test.',
        ]);

        $headers = $this->authHeaders('lead@technova.fr');

        $forbiddenResponse = $this->postJson('/api/invitation-codes', [
            'team_id' => $otherTeam->id,
            'target_role' => UserRole::DEVELOPER->value,
            'first_name' => 'Mila',
            'last_name' => 'Dupont',
            'email' => 'mila.dupont@technova.fr',
        ], $headers);
        $forbiddenResponse->assertStatus(422);

        $leadTeam = Team::query()->where('name', 'Equipe API')->firstOrFail();
        $successResponse = $this->postJson('/api/invitation-codes', [
            'team_id' => $leadTeam->id,
            'target_role' => UserRole::DEVELOPER->value,
            'first_name' => 'Mila',
            'last_name' => 'Dupont',
            'email' => 'mila.dupont@technova.fr',
        ], $headers);
        $successResponse->assertStatus(201);
    }

    public function test_register_requires_invitation_email_match(): void
    {
        $invitation = InvitationCode::query()->where('code', 'INVITE-DEV-TECHNOVA')->firstOrFail();

        $mismatchResponse = $this->postJson('/api/register', [
            'invitation_code' => $invitation->code,
            'email' => 'wrong.email@technova.fr',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);
        $mismatchResponse->assertStatus(422);

        $successResponse = $this->postJson('/api/register', [
            'invitation_code' => $invitation->code,
            'email' => $invitation->email,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $successResponse->assertStatus(201);
        $successResponse->assertJsonPath('data.user.role', UserRole::DEVELOPER->value);
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


<?php

namespace Tests\Feature;

use App\Models\InvitationCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvitationRegisterFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_register_with_valid_invitation_marks_code_as_used_and_creates_membership(): void
    {
        $invitation = InvitationCode::query()->where('code', 'INVITE-DEV-TECHNOVA')->firstOrFail();

        $response = $this->postJson('/api/register', [
            'invitation_code' => $invitation->code,
            'email' => $invitation->email,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201);

        $createdUser = User::query()->where('email', $invitation->email)->firstOrFail();
        $this->assertSame($invitation->organization_id, $createdUser->organization_id);
        $this->assertNotNull(InvitationCode::query()->find($invitation->id)?->used_at);
        $this->assertDatabaseHas('team_memberships', [
            'organization_id' => $invitation->organization_id,
            'team_id' => $invitation->team_id,
            'user_id' => $createdUser->id,
        ]);
    }

    public function test_register_rejects_reused_invitation_code(): void
    {
        $invitation = InvitationCode::query()->where('code', 'INVITE-DEV-TECHNOVA')->firstOrFail();

        $firstResponse = $this->postJson('/api/register', [
            'invitation_code' => $invitation->code,
            'email' => $invitation->email,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);
        $firstResponse->assertStatus(201);

        $secondResponse = $this->postJson('/api/register', [
            'invitation_code' => $invitation->code,
            'email' => 'another.user@technova.fr',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);
        $secondResponse->assertStatus(422);
    }
}

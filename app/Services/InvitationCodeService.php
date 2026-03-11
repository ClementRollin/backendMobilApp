<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\InvitationCode;
use App\Models\Team;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class InvitationCodeService
{
    public function __construct(private readonly AccessService $accessService)
    {
    }

    public function listForUser(User $user): Collection
    {
        $query = InvitationCode::query()
            ->with(['team', 'createdBy'])
            ->where('organization_id', $user->organization_id)
            ->orderByDesc('created_at');

        if ($this->accessService->isCto($user)) {
            return $query->get();
        }

        return $query->whereIn('team_id', $this->accessService->leadTeamIds($user))->get();
    }

    public function create(User $user, array $payload): InvitationCode
    {
        $teamId = $payload['team_id'] ?? null;
        if ($teamId === null) {
            throw ValidationException::withMessages([
                'team_id' => ['team_id is required for invitation creation.'],
            ]);
        }

        $team = Team::query()->findOrFail($teamId);
        if ((int) $team->organization_id !== (int) $user->organization_id) {
            throw ValidationException::withMessages([
                'team_id' => ['The selected team is not in your organization.'],
            ]);
        }

        $targetRole = $payload['target_role'];

        if ($this->accessService->isCto($user)) {
            if (! in_array($targetRole, [UserRole::LEAD_DEV->value, UserRole::PO->value], true)) {
                throw ValidationException::withMessages([
                    'target_role' => ['CTO can only invite lead_dev or po users.'],
                ]);
            }
        } elseif ($this->accessService->isLead($user)) {
            if (! $this->accessService->isLeadOfTeam($user, (int) $teamId)) {
                throw ValidationException::withMessages([
                    'team_id' => ['You can only create invitations for teams where you are lead.'],
                ]);
            }

            if ($targetRole !== UserRole::DEVELOPER->value) {
                throw ValidationException::withMessages([
                    'target_role' => ['Lead can only invite developer users.'],
                ]);
            }
        } else {
            throw new AuthorizationException('You are not allowed to create invitations.');
        }

        return InvitationCode::query()->create([
            'code' => $this->generateCode(),
            'organization_id' => $user->organization_id,
            'team_id' => $teamId,
            'target_role' => $targetRole,
            'first_name' => $payload['first_name'],
            'last_name' => $payload['last_name'],
            'email' => mb_strtolower($payload['email']),
            'created_by_user_id' => $user->id,
        ]);
    }

    public function revoke(User $user, InvitationCode $invitationCode): InvitationCode
    {
        if ((int) $invitationCode->organization_id !== (int) $user->organization_id) {
            throw new AuthorizationException('Invitation is outside your organization.');
        }

        if ($this->accessService->isLead($user)
            && ($invitationCode->team_id === null || ! $this->accessService->isLeadOfTeam($user, $invitationCode->team_id))) {
            throw new AuthorizationException('You can only revoke invitations for your lead teams.');
        }

        $invitationCode->revoked_at = now();
        $invitationCode->save();

        return $invitationCode;
    }

    private function generateCode(): string
    {
        do {
            $code = 'INV-'.Str::upper(Str::random(10));
        } while (InvitationCode::query()->where('code', $code)->exists());

        return $code;
    }
}

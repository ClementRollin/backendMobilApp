<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Team;
use App\Models\TeamMembership;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class TeamMembershipService
{
    public function __construct(private readonly AccessService $accessService)
    {
    }

    public function listForTeam(User $actor, Team $team): Collection
    {
        if ((int) $team->organization_id !== (int) $actor->organization_id) {
            throw new AuthorizationException('Team is outside your organization.');
        }

        return TeamMembership::query()
            ->with('user')
            ->where('organization_id', $actor->organization_id)
            ->where('team_id', $team->id)
            ->orderBy('created_at')
            ->get();
    }

    public function addMembership(User $actor, Team $team, int $userId): TeamMembership
    {
        if ((int) $team->organization_id !== (int) $actor->organization_id) {
            throw new AuthorizationException('Team is outside your organization.');
        }

        $targetUser = User::query()->findOrFail($userId);

        if ((int) $targetUser->organization_id !== (int) $actor->organization_id) {
            throw ValidationException::withMessages([
                'user_id' => ['Target user is outside your organization.'],
            ]);
        }

        if ($this->accessService->isLead($actor)
            && ! in_array($targetUser->role, [UserRole::DEVELOPER, UserRole::PO], true)) {
            throw ValidationException::withMessages([
                'user_id' => ['Lead can only manage developer or po memberships.'],
            ]);
        }

        if ($targetUser->role === UserRole::DEVELOPER) {
            $alreadyInTeam = TeamMembership::query()
                ->where('organization_id', $actor->organization_id)
                ->where('user_id', $targetUser->id)
                ->exists();
            if ($alreadyInTeam) {
                throw ValidationException::withMessages([
                    'user_id' => ['A developer can only belong to one team.'],
                ]);
            }
        }

        return TeamMembership::query()->create([
            'organization_id' => $actor->organization_id,
            'team_id' => $team->id,
            'user_id' => $targetUser->id,
        ]);
    }

    public function removeMembership(User $actor, TeamMembership $membership): void
    {
        if ((int) $membership->organization_id !== (int) $actor->organization_id) {
            throw new AuthorizationException('Membership is outside your organization.');
        }

        $membership->delete();
    }
}

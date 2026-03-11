<?php

namespace App\Policies;

use App\Models\Team;
use App\Models\User;
use App\Services\AccessService;

class TeamPolicy
{
    public function __construct(private readonly AccessService $accessService)
    {
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Team $team): bool
    {
        if ((int) $team->organization_id !== (int) $user->organization_id) {
            return false;
        }

        if ($this->accessService->isCto($user)) {
            return true;
        }

        return in_array((int) $team->id, $this->accessService->userTeamIds($user), true);
    }

    public function create(User $user): bool
    {
        return $this->accessService->isCto($user);
    }

    public function update(User $user, Team $team): bool
    {
        return $this->accessService->isCto($user)
            && (int) $team->organization_id === (int) $user->organization_id;
    }

    public function delete(User $user, Team $team): bool
    {
        return $this->update($user, $team);
    }

    public function manageMemberships(User $user, Team $team): bool
    {
        if ((int) $team->organization_id !== (int) $user->organization_id) {
            return false;
        }

        if ($this->accessService->isCto($user)) {
            return true;
        }

        return $this->accessService->isLeadOfTeam($user, $team->id);
    }
}

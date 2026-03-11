<?php

namespace App\Policies;

use App\Models\Team;
use App\Models\TeamMembership;
use App\Models\User;

class TeamMembershipPolicy
{
    public function __construct(private readonly TeamPolicy $teamPolicy)
    {
    }

    public function viewAny(User $user, Team $team): bool
    {
        return $this->teamPolicy->manageMemberships($user, $team)
            || $this->teamPolicy->view($user, $team);
    }

    public function create(User $user, Team $team): bool
    {
        return $this->teamPolicy->manageMemberships($user, $team);
    }

    public function delete(User $user, TeamMembership $teamMembership): bool
    {
        $team = $teamMembership->team;
        if ($team === null) {
            return false;
        }

        return $this->teamPolicy->manageMemberships($user, $team);
    }
}


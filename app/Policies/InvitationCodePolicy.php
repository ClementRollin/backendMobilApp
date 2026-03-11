<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\InvitationCode;
use App\Models\User;
use App\Services\AccessService;

class InvitationCodePolicy
{
    public function __construct(private readonly AccessService $accessService)
    {
    }

    public function viewAny(User $user): bool
    {
        return $this->accessService->isCto($user) || $this->accessService->isLead($user);
    }

    public function view(User $user, InvitationCode $invitationCode): bool
    {
        if ((int) $invitationCode->organization_id !== (int) $user->organization_id) {
            return false;
        }

        if ($this->accessService->isCto($user)) {
            return true;
        }

        if (! $this->accessService->isLead($user)) {
            return false;
        }

        if ($invitationCode->team_id === null) {
            return false;
        }

        return $this->accessService->isLeadOfTeam($user, $invitationCode->team_id);
    }

    public function create(User $user): bool
    {
        return $this->accessService->isCto($user) || $this->accessService->isLead($user);
    }

    public function revoke(User $user, InvitationCode $invitationCode): bool
    {
        return $this->view($user, $invitationCode);
    }
}


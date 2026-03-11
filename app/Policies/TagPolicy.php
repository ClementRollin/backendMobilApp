<?php

namespace App\Policies;

use App\Models\Tag;
use App\Models\User;
use App\Services\AccessService;

class TagPolicy
{
    public function __construct(private readonly AccessService $accessService)
    {
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Tag $tag): bool
    {
        return (int) $tag->organization_id === (int) $user->organization_id;
    }

    public function create(User $user): bool
    {
        return $this->accessService->isLead($user);
    }

    public function update(User $user, Tag $tag): bool
    {
        return $this->accessService->isLead($user)
            && (int) $tag->organization_id === (int) $user->organization_id;
    }

    public function delete(User $user, Tag $tag): bool
    {
        return $this->update($user, $tag);
    }
}


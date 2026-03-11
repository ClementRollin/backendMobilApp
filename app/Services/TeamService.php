<?php

namespace App\Services;

use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class TeamService
{
    public function __construct(private readonly AccessService $accessService)
    {
    }

    public function listForUser(User $user): Collection
    {
        $query = Team::query()->where('organization_id', $user->organization_id)->orderBy('name');

        if ($this->accessService->isCto($user)) {
            return $query->get();
        }

        return $query->whereIn('id', $this->accessService->userTeamIds($user))->get();
    }

    public function create(User $user, array $payload): Team
    {
        return Team::query()->create([
            'organization_id' => $user->organization_id,
            'name' => $payload['name'],
            'description' => $payload['description'] ?? null,
        ]);
    }
}


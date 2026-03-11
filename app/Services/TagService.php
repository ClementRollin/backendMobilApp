<?php

namespace App\Services;

use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class TagService
{
    public function listForUser(User $user): Collection
    {
        return Tag::query()
            ->where('organization_id', $user->organization_id)
            ->orderBy('name')
            ->get();
    }

    public function create(User $user, array $payload): Tag
    {
        return Tag::query()->create([
            'organization_id' => $user->organization_id,
            'name' => $payload['name'],
            'color' => $payload['color'] ?? null,
        ]);
    }

    public function update(Tag $tag, array $payload): Tag
    {
        $tag->fill([
            'name' => $payload['name'],
            'color' => $payload['color'] ?? null,
        ])->save();

        return $tag;
    }
}


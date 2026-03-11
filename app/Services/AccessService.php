<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Task;
use App\Models\Team;
use App\Models\TeamMembership;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class AccessService
{
    private function roleValue(User $user): string
    {
        return $user->role instanceof UserRole
            ? $user->role->value
            : (string) $user->role;
    }

    public function isCto(User $user): bool
    {
        return $this->roleValue($user) === UserRole::CTO->value;
    }

    public function isLead(User $user): bool
    {
        return $this->roleValue($user) === UserRole::LEAD_DEV->value;
    }

    public function isDeveloper(User $user): bool
    {
        return $this->roleValue($user) === UserRole::DEVELOPER->value;
    }

    public function isPo(User $user): bool
    {
        return $this->roleValue($user) === UserRole::PO->value;
    }

    public function userTeamIds(User $user): array
    {
        return TeamMembership::query()
            ->where('organization_id', $user->organization_id)
            ->where('user_id', $user->id)
            ->pluck('team_id')
            ->all();
    }

    public function leadTeamIds(User $user): array
    {
        if (! $this->isLead($user)) {
            return [];
        }

        return $this->userTeamIds($user);
    }

    public function userTeams(User $user): Collection
    {
        return Team::query()
            ->where('organization_id', $user->organization_id)
            ->whereIn('id', $this->userTeamIds($user))
            ->get();
    }

    public function isLeadOfTeam(User $user, int $teamId): bool
    {
        if (! $this->isLead($user)) {
            return false;
        }

        return TeamMembership::query()
            ->where('organization_id', $user->organization_id)
            ->where('team_id', $teamId)
            ->where('user_id', $user->id)
            ->exists();
    }

    public function ensureSameOrganization(User $user, int $organizationId): void
    {
        if ((int) $user->organization_id !== (int) $organizationId) {
            throw ValidationException::withMessages([
                'organization_id' => ['The selected organization is not accessible.'],
            ]);
        }
    }

    public function canAccessTask(User $user, Task $task): bool
    {
        if ((int) $task->organization_id !== (int) $user->organization_id) {
            return false;
        }

        if ($this->isCto($user)) {
            return true;
        }

        if ($this->isLead($user)) {
            return in_array((int) $task->team_id, $this->leadTeamIds($user), true);
        }

        if ($this->isDeveloper($user)) {
            return (int) $task->assignee_id === (int) $user->id;
        }

        if ($this->isPo($user)) {
            return in_array((int) $task->team_id, $this->userTeamIds($user), true);
        }

        return false;
    }

    public function canManageTask(User $user, Task $task): bool
    {
        return $this->isLead($user)
            && (int) $task->organization_id === (int) $user->organization_id
            && in_array((int) $task->team_id, $this->leadTeamIds($user), true);
    }

    public function canCreateTaskInTeam(User $user, int $teamId): bool
    {
        return $this->isLeadOfTeam($user, $teamId);
    }

    public function applyTaskScope(Builder $query, User $user, string $scope): Builder
    {
        $query->where('organization_id', $user->organization_id);

        return match ($scope) {
            'created' => $this->applyCreatedScope($query, $user),
            'assigned' => $query->where('assignee_id', $user->id),
            'unassigned' => $this->applyUnassignedScope($query, $user),
            default => $this->applyVisibleScope($query, $user),
        };
    }

    private function applyVisibleScope(Builder $query, User $user): Builder
    {
        if ($this->isCto($user)) {
            return $query;
        }

        if ($this->isLead($user)) {
            return $query->whereIn('team_id', $this->leadTeamIds($user));
        }

        if ($this->isDeveloper($user)) {
            return $query->where('assignee_id', $user->id);
        }

        if ($this->isPo($user)) {
            return $query->whereIn('team_id', $this->userTeamIds($user));
        }

        return $query->whereRaw('1 = 0');
    }

    private function applyCreatedScope(Builder $query, User $user): Builder
    {
        if (! $this->isLead($user)) {
            return $query->whereRaw('1 = 0');
        }

        return $query
            ->where('creator_id', $user->id)
            ->whereIn('team_id', $this->leadTeamIds($user));
    }

    private function applyUnassignedScope(Builder $query, User $user): Builder
    {
        if (! $this->isLead($user)) {
            return $query->whereRaw('1 = 0');
        }

        return $query
            ->whereNull('assignee_id')
            ->whereIn('team_id', $this->leadTeamIds($user));
    }
}

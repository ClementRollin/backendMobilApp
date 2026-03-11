<?php

namespace App\Http\Requests\TeamMembership;

use App\Enums\UserRole;
use App\Models\Team;
use App\Models\TeamMembership;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreTeamMembershipRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $actor = $this->user();
            if (! $actor) {
                return;
            }

            /** @var Team|null $team */
            $team = $this->route('team');
            if (! $team || (int) $team->organization_id !== (int) $actor->organization_id) {
                $validator->errors()->add('team', 'The selected team is not in your organization.');
                return;
            }

            $target = User::query()->find($this->integer('user_id'));
            if (! $target || (int) $target->organization_id !== (int) $actor->organization_id) {
                $validator->errors()->add('user_id', 'The selected user is not in your organization.');
                return;
            }

            $exists = TeamMembership::query()
                ->where('team_id', $team->id)
                ->where('user_id', $target->id)
                ->exists();
            if ($exists) {
                $validator->errors()->add('user_id', 'This user is already a member of the selected team.');
            }

            if ($target->role === UserRole::DEVELOPER) {
                $alreadyInAnyTeam = TeamMembership::query()
                    ->where('organization_id', $actor->organization_id)
                    ->where('user_id', $target->id)
                    ->exists();
                if ($alreadyInAnyTeam) {
                    $validator->errors()->add('user_id', 'A developer can only belong to one team.');
                }
            }
        });
    }
}


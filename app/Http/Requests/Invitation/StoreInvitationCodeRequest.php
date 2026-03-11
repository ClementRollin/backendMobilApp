<?php

namespace App\Http\Requests\Invitation;

use App\Enums\UserRole;
use App\Models\Team;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreInvitationCodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'team_id' => ['nullable', 'integer', 'exists:teams,id'],
            'target_role' => ['required', 'in:'.implode(',', UserRole::values())],
            'first_name' => ['required', 'string', 'max:120'],
            'last_name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $actor = $this->user();
            if (! $actor) {
                return;
            }

            $teamId = $this->input('team_id');
            if ($teamId !== null) {
                $team = Team::query()->find((int) $teamId);
                if (! $team || (int) $team->organization_id !== (int) $actor->organization_id) {
                    $validator->errors()->add('team_id', 'The selected team is not in your organization.');
                }
            }

            $targetRole = $this->input('target_role');
            if ($actor->role === UserRole::CTO && $targetRole !== UserRole::LEAD_DEV->value) {
                $validator->errors()->add('target_role', 'CTO can only invite lead_dev users.');
            }

            if ($actor->role === UserRole::LEAD_DEV
                && ! in_array($targetRole, [UserRole::DEVELOPER->value, UserRole::PO->value], true)) {
                $validator->errors()->add('target_role', 'Lead can only invite developer or po users.');
            }
        });
    }
}


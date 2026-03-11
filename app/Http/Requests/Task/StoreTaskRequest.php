<?php

namespace App\Http\Requests\Task;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Tag;
use App\Models\Team;
use App\Models\TeamMembership;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'team_id' => ['required', 'integer', 'exists:teams,id'],
            'title' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:3000'],
            'assignee_id' => ['nullable', 'integer', 'exists:users,id'],
            'status' => ['required', 'in:'.implode(',', TaskStatus::values())],
            'priority' => ['required', 'in:'.implode(',', TaskPriority::values())],
            'blocked_reason' => ['nullable', 'string', 'max:3000'],
            'due_date' => ['nullable', 'date'],
            'tag_ids' => ['nullable', 'array'],
            'tag_ids.*' => ['integer', 'exists:tags,id'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $user = $this->user();
            if (! $user) {
                return;
            }

            $teamId = $this->integer('team_id');
            $team = Team::query()->find($teamId);
            if (! $team || (int) $team->organization_id !== (int) $user->organization_id) {
                $validator->errors()->add('team_id', 'The selected team is not in your organization.');
            }

            $assigneeId = $this->input('assignee_id');
            if ($assigneeId !== null) {
                $assignee = User::query()->find($assigneeId);
                if (! $assignee || (int) $assignee->organization_id !== (int) $user->organization_id) {
                    $validator->errors()->add('assignee_id', 'The selected assignee is not in your organization.');
                } elseif ($team !== null) {
                    $isMember = TeamMembership::query()
                        ->where('organization_id', $user->organization_id)
                        ->where('team_id', $team->id)
                        ->where('user_id', $assignee->id)
                        ->exists();
                    if (! $isMember) {
                        $validator->errors()->add('assignee_id', 'The selected assignee is not a member of this team.');
                    }
                }
            }

            if ($this->input('status') === TaskStatus::BLOCKED->value && ! $this->filled('blocked_reason')) {
                $validator->errors()->add('blocked_reason', 'Blocked reason is required when status is blocked.');
            }

            $tagIds = $this->input('tag_ids', []);
            if (! is_array($tagIds) || $tagIds === []) {
                return;
            }

            $count = Tag::query()
                ->where('organization_id', $user->organization_id)
                ->whereIn('id', $tagIds)
                ->count();

            if ($count !== count(array_unique($tagIds))) {
                $validator->errors()->add('tag_ids', 'One or more tags are not in your organization.');
            }
        });
    }
}

<?php

namespace App\Http\Requests\Task;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Enums\UserRole;
use App\Models\Tag;
use App\Models\Team;
use App\Models\TeamMembership;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateTaskRequest extends FormRequest
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
            'priority' => ['required', 'in:'.implode(',', TaskPriority::values())],
            'blocked_reason' => ['nullable', 'string', 'max:3000'],
            'due_date' => ['nullable', 'date'],
            'tag_ids' => ['nullable', 'array'],
            'tag_ids.*' => ['integer', 'exists:tags,id'],
            'status' => ['prohibited'],
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

                $assigneeRole = $assignee?->role instanceof UserRole ? $assignee->role->value : (string) $assignee?->role;
                $isSelfAssignment = $assignee !== null && (int) $assignee->id === (int) $user->id;
                $isAllowedAssignee = $assigneeRole === UserRole::DEVELOPER->value || $isSelfAssignment;
                if ($assignee !== null && ! $isAllowedAssignee) {
                    $validator->errors()->add('assignee_id', 'Assignee must be a developer or yourself.');
                }

                if ($assigneeRole === UserRole::DEVELOPER->value && $assignee !== null) {
                    $membershipCount = TeamMembership::query()
                        ->where('organization_id', $user->organization_id)
                        ->where('user_id', $assignee->id)
                        ->count();

                    if ($membershipCount > 1) {
                        $validator->errors()->add('assignee_id', 'The selected developer belongs to multiple teams.');
                    }
                }
            }

            $task = $this->route('task');
            $taskStatus = $task?->status instanceof TaskStatus ? $task->status->value : (string) $task?->status;
            if ($taskStatus === TaskStatus::BLOCKED->value && ! $this->filled('blocked_reason')) {
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

    public function messages(): array
    {
        return [
            'status.prohibited' => 'Status updates must use PATCH /api/tasks/{task}/status.',
        ];
    }
}

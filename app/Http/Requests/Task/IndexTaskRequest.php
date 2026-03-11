<?php

namespace App\Http\Requests\Task;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class IndexTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'scope' => ['nullable', 'in:visible,created,assigned,unassigned'],
            'status' => ['nullable', 'in:'.implode(',', TaskStatus::values())],
            'priority' => ['nullable', 'in:'.implode(',', TaskPriority::values())],
            'team_id' => ['nullable', 'integer', 'exists:teams,id'],
            'assignee_id' => ['nullable', 'integer', 'exists:users,id'],
            'creator_id' => ['nullable', 'integer', 'exists:users,id'],
            'due_before' => ['nullable', 'date'],
            'due_after' => ['nullable', 'date'],
            'search' => ['nullable', 'string', 'max:255'],
            'tag_ids' => ['nullable', 'array'],
            'tag_ids.*' => ['integer', 'exists:tags,id'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $user = $this->user();
            $dueBefore = $this->input('due_before');
            $dueAfter = $this->input('due_after');
            $creatorId = $this->input('creator_id');

            if ($dueBefore === null || $dueAfter === null) {
                // continue
            } elseif (strtotime((string) $dueAfter) > strtotime((string) $dueBefore)) {
                $validator->errors()->add('due_after', 'The due_after value must be before or equal to due_before.');
            }

            if ($user !== null && $creatorId !== null) {
                $creator = User::query()->find((int) $creatorId);
                if ($creator === null || (int) $creator->organization_id !== (int) $user->organization_id) {
                    $validator->errors()->add('creator_id', 'The selected creator is not in your organization.');
                }
            }
        });
    }
}

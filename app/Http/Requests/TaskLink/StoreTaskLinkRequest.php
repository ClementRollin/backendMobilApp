<?php

namespace App\Http\Requests\TaskLink;

use App\Models\Task;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreTaskLinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'linked_task_id' => ['required', 'integer', 'exists:tasks,id'],
            'link_type' => ['nullable', 'string', 'max:40'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $user = $this->user();
            if (! $user) {
                return;
            }

            /** @var Task|null $task */
            $task = $this->route('task');
            $linkedTask = Task::query()->find($this->integer('linked_task_id'));

            if (! $task || ! $linkedTask) {
                return;
            }

            if ((int) $task->organization_id !== (int) $user->organization_id
                || (int) $linkedTask->organization_id !== (int) $user->organization_id) {
                $validator->errors()->add('linked_task_id', 'Linked task must belong to your organization.');
            }

            if ((int) $task->id === (int) $linkedTask->id) {
                $validator->errors()->add('linked_task_id', 'A task cannot be linked to itself.');
            }
        });
    }
}


<?php

namespace App\Http\Requests\TaskLink;

use App\Models\Task;
use App\Models\TaskLink;
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

            if ((int) $task->id === (int) $linkedTask->id) {
                $validator->errors()->add('linked_task_id', 'A task cannot be linked to itself.');
                return;
            }

            $lowId = min((int) $task->id, (int) $linkedTask->id);
            $highId = max((int) $task->id, (int) $linkedTask->id);

            $alreadyExists = TaskLink::query()
                ->where('organization_id', $user->organization_id)
                ->where('task_low_id', $lowId)
                ->where('task_high_id', $highId)
                ->exists();

            if ($alreadyExists) {
                $validator->errors()->add('linked_task_id', 'A link already exists between these tasks.');
            }
        });
    }
}

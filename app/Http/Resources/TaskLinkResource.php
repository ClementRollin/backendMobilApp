<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\TaskLink */
class TaskLinkResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $contextTask = $request->route('task');
        $linkedTask = null;
        if ($contextTask !== null) {
            $linkedTask = (int) $contextTask->id === (int) $this->task_low_id
                ? $this->highTask
                : $this->lowTask;
        }

        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'task_low_id' => $this->task_low_id,
            'task_high_id' => $this->task_high_id,
            'link_type' => $this->link_type,
            'linked_task' => $linkedTask ? [
                'id' => $linkedTask->id,
                'title' => $linkedTask->title,
                'status' => $linkedTask->status?->value ?? $linkedTask->status,
                'priority' => $linkedTask->priority?->value ?? $linkedTask->priority,
                'assignee' => $linkedTask->assignee
                    ? UserResource::make($linkedTask->assignee)->resolve()
                    : null,
            ] : null,
            'created_at' => optional($this->created_at)->toIso8601String(),
            'updated_at' => optional($this->updated_at)->toIso8601String(),
        ];
    }
}

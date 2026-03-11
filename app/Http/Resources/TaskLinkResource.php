<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\TaskLink */
class TaskLinkResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'task_low_id' => $this->task_low_id,
            'task_high_id' => $this->task_high_id,
            'link_type' => $this->link_type,
            'created_at' => optional($this->created_at)->toIso8601String(),
            'updated_at' => optional($this->updated_at)->toIso8601String(),
            'low_task' => TaskResource::make($this->whenLoaded('lowTask')),
            'high_task' => TaskResource::make($this->whenLoaded('highTask')),
        ];
    }
}


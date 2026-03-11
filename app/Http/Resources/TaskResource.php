<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Task */
class TaskResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'team_id' => $this->team_id,
            'creator_id' => $this->creator_id,
            'assignee_id' => $this->assignee_id,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status?->value ?? $this->status,
            'priority' => $this->priority?->value ?? $this->priority,
            'blocked_reason' => $this->blocked_reason,
            'blocked_confirmed_at' => optional($this->blocked_confirmed_at)->toIso8601String(),
            'blocked_confirmed_by' => $this->blocked_confirmed_by,
            'deployed_at' => optional($this->deployed_at)->toIso8601String(),
            'due_date' => optional($this->due_date)->toIso8601String(),
            'created_at' => optional($this->created_at)->toIso8601String(),
            'updated_at' => optional($this->updated_at)->toIso8601String(),
            'creator' => UserResource::make($this->whenLoaded('creator')),
            'assignee' => UserResource::make($this->whenLoaded('assignee')),
            'blocked_confirmed_user' => UserResource::make($this->whenLoaded('blockedConfirmedBy')),
            'tags' => TagResource::collection($this->whenLoaded('tags')),
        ];
    }
}

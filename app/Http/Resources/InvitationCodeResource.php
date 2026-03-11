<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\InvitationCode */
class InvitationCodeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'organization_id' => $this->organization_id,
            'team_id' => $this->team_id,
            'target_role' => $this->target_role,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'created_by_user_id' => $this->created_by_user_id,
            'used_at' => optional($this->used_at)->toIso8601String(),
            'revoked_at' => optional($this->revoked_at)->toIso8601String(),
            'created_at' => optional($this->created_at)->toIso8601String(),
            'updated_at' => optional($this->updated_at)->toIso8601String(),
            'team' => TeamResource::make($this->whenLoaded('team')),
            'created_by' => UserResource::make($this->whenLoaded('createdBy')),
        ];
    }
}


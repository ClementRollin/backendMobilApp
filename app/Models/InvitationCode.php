<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvitationCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'organization_id',
        'team_id',
        'target_role',
        'first_name',
        'last_name',
        'email',
        'created_by_user_id',
        'used_at',
        'revoked_at',
    ];

    protected function casts(): array
    {
        return [
            'used_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}


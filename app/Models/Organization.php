<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Organization extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function teams(): HasMany
    {
        return $this->hasMany(Team::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function invitationCodes(): HasMany
    {
        return $this->hasMany(InvitationCode::class);
    }

    public function tags(): HasMany
    {
        return $this->hasMany(Tag::class);
    }

    public function taskLinks(): HasMany
    {
        return $this->hasMany(TaskLink::class);
    }

    public function taskStatusHistories(): HasMany
    {
        return $this->hasMany(TaskStatusHistory::class);
    }

    public function devicePushTokens(): HasMany
    {
        return $this->hasMany(DevicePushToken::class);
    }
}


<?php

namespace App\Models;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Task extends Model
{
    use HasFactory;

    public const STATUS_TODO = TaskStatus::TODO->value;
    public const STATUS_IN_PROGRESS = TaskStatus::IN_PROGRESS->value;
    public const STATUS_BLOCKED = TaskStatus::BLOCKED->value;
    public const STATUS_IN_REVIEW = TaskStatus::IN_REVIEW->value;
    public const STATUS_WAITING_FOR_TEST = TaskStatus::WAITING_FOR_TEST->value;
    public const STATUS_TESTED = TaskStatus::TESTED->value;
    public const STATUS_DEPLOYED = TaskStatus::DEPLOYED->value;

    public const PRIORITY_LOW = TaskPriority::LOW->value;
    public const PRIORITY_MEDIUM = TaskPriority::MEDIUM->value;
    public const PRIORITY_HIGH = TaskPriority::HIGH->value;

    protected $fillable = [
        'organization_id',
        'team_id',
        'creator_id',
        'assignee_id',
        'title',
        'description',
        'status',
        'priority',
        'blocked_reason',
        'blocked_confirmed_at',
        'blocked_confirmed_by',
        'deployed_at',
        'due_date',
    ];

    protected function casts(): array
    {
        return [
            'status' => TaskStatus::class,
            'priority' => TaskPriority::class,
            'due_date' => 'datetime',
            'blocked_confirmed_at' => 'datetime',
            'deployed_at' => 'datetime',
        ];
    }

    public function scopeForOrganization(Builder $query, int $organizationId): Builder
    {
        return $query->where('organization_id', $organizationId);
    }

    public function scopeUnassigned(Builder $query): Builder
    {
        return $query->whereNull('assignee_id');
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function blockedConfirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'blocked_confirmed_by');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'task_tag')->withTimestamps();
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(TaskStatusHistory::class);
    }

    public function outgoingLinks(): HasMany
    {
        return $this->hasMany(TaskLink::class, 'task_low_id');
    }

    public function incomingLinks(): HasMany
    {
        return $this->hasMany(TaskLink::class, 'task_high_id');
    }
}

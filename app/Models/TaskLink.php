<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskLink extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'task_low_id',
        'task_high_id',
        'link_type',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function lowTask(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'task_low_id');
    }

    public function highTask(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'task_high_id');
    }
}


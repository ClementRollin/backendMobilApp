<?php

namespace App\Providers;

use App\Models\InvitationCode;
use App\Models\Tag;
use App\Models\Task;
use App\Models\TaskLink;
use App\Models\TaskStatusHistory;
use App\Models\Team;
use App\Models\TeamMembership;
use App\Policies\InvitationCodePolicy;
use App\Policies\TagPolicy;
use App\Policies\TaskPolicy;
use App\Policies\TaskLinkPolicy;
use App\Policies\TaskStatusHistoryPolicy;
use App\Policies\TeamMembershipPolicy;
use App\Policies\TeamPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Task::class => TaskPolicy::class,
        Team::class => TeamPolicy::class,
        TeamMembership::class => TeamMembershipPolicy::class,
        InvitationCode::class => InvitationCodePolicy::class,
        Tag::class => TagPolicy::class,
        TaskLink::class => TaskLinkPolicy::class,
        TaskStatusHistory::class => TaskStatusHistoryPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}

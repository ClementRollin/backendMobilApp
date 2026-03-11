<?php

namespace Database\Seeders;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class TaskSeeder extends Seeder
{
    public function run(): void
    {
        $lead = User::query()->where('email', 'lead@technova.fr')->firstOrFail();
        $developer = User::query()->where('email', 'dev@technova.fr')->firstOrFail();
        $apiTeam = Team::query()->where('name', 'Equipe API')->firstOrFail();
        $mobileTeam = Team::query()->where('name', 'Equipe Mobile')->firstOrFail();

        Task::query()->delete();

        Task::query()->create([
            'organization_id' => $lead->organization_id,
            'team_id' => $apiTeam->id,
            'creator_id' => $lead->id,
            'assignee_id' => $developer->id,
            'title' => 'Finaliser API Auth',
            'description' => 'Stabiliser le flux de connexion mobile.',
            'status' => TaskStatus::IN_REVIEW->value,
            'priority' => TaskPriority::HIGH->value,
            'due_date' => Carbon::now()->addHours(10)->toIso8601String(),
        ]);

        Task::query()->create([
            'organization_id' => $lead->organization_id,
            'team_id' => $mobileTeam->id,
            'creator_id' => $lead->id,
            'assignee_id' => null,
            'title' => 'Mettre en place le Kanban mobile',
            'description' => 'Vue lead en colonnes avec filtres principaux.',
            'status' => TaskStatus::TODO->value,
            'priority' => TaskPriority::MEDIUM->value,
            'due_date' => Carbon::now()->addDays(2)->toIso8601String(),
        ]);

        Task::query()->create([
            'organization_id' => $lead->organization_id,
            'team_id' => $apiTeam->id,
            'creator_id' => $lead->id,
            'assignee_id' => $developer->id,
            'title' => 'Stabiliser la campagne de tests',
            'description' => 'Corriger les echecs intermittents CI.',
            'status' => TaskStatus::BLOCKED->value,
            'priority' => TaskPriority::HIGH->value,
            'blocked_reason' => 'Dependance externe indisponible.',
            'blocked_confirmed_at' => Carbon::now()->subHours(2)->toIso8601String(),
            'blocked_confirmed_by' => $lead->id,
            'due_date' => Carbon::now()->addHours(20)->toIso8601String(),
        ]);

        Task::query()->create([
            'organization_id' => $lead->organization_id,
            'team_id' => $apiTeam->id,
            'creator_id' => $lead->id,
            'assignee_id' => $developer->id,
            'title' => 'Publier la release 1.0',
            'description' => 'Release de stabilisation du sprint courant.',
            'status' => TaskStatus::DEPLOYED->value,
            'priority' => TaskPriority::LOW->value,
            'deployed_at' => Carbon::now()->subMonths(4)->toIso8601String(),
            'due_date' => Carbon::now()->subMonths(4)->toIso8601String(),
        ]);
    }
}


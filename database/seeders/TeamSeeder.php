<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\Team;
use App\Models\TeamMembership;
use App\Models\User;
use Illuminate\Database\Seeder;

class TeamSeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::query()->where('slug', 'technova')->firstOrFail();
        $lead = User::query()->where('email', 'lead@technova.fr')->firstOrFail();
        $developer = User::query()->where('email', 'dev@technova.fr')->firstOrFail();
        $po = User::query()->where('email', 'po@technova.fr')->firstOrFail();

        $apiTeam = Team::query()->updateOrCreate(
            ['organization_id' => $organization->id, 'name' => 'Equipe API'],
            ['description' => 'Equipe backend et plateforme.']
        );

        $mobileTeam = Team::query()->updateOrCreate(
            ['organization_id' => $organization->id, 'name' => 'Equipe Mobile'],
            ['description' => 'Equipe application mobile.']
        );

        TeamMembership::query()->updateOrCreate(
            ['team_id' => $apiTeam->id, 'user_id' => $lead->id],
            ['organization_id' => $organization->id]
        );
        TeamMembership::query()->updateOrCreate(
            ['team_id' => $mobileTeam->id, 'user_id' => $lead->id],
            ['organization_id' => $organization->id]
        );
        TeamMembership::query()->updateOrCreate(
            ['team_id' => $apiTeam->id, 'user_id' => $developer->id],
            ['organization_id' => $organization->id]
        );
        TeamMembership::query()->updateOrCreate(
            ['team_id' => $mobileTeam->id, 'user_id' => $po->id],
            ['organization_id' => $organization->id]
        );
    }
}


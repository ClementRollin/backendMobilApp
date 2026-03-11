<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\InvitationCode;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;

class InvitationCodeSeeder extends Seeder
{
    public function run(): void
    {
        InvitationCode::query()->delete();

        $cto = User::query()->where('email', 'cto@technova.fr')->firstOrFail();
        $lead = User::query()->where('email', 'lead@technova.fr')->firstOrFail();
        $mobileTeam = Team::query()->where('name', 'Equipe Mobile')->firstOrFail();
        $apiTeam = Team::query()->where('name', 'Equipe API')->firstOrFail();

        InvitationCode::query()->create([
            'code' => 'INVITE-LEAD-TECHNOVA',
            'organization_id' => $cto->organization_id,
            'team_id' => $mobileTeam->id,
            'target_role' => UserRole::LEAD_DEV->value,
            'first_name' => 'Camille',
            'last_name' => 'Roussel',
            'email' => 'camille.roussel@technova.fr',
            'created_by_user_id' => $cto->id,
        ]);

        InvitationCode::query()->create([
            'code' => 'INVITE-DEV-TECHNOVA',
            'organization_id' => $lead->organization_id,
            'team_id' => $apiTeam->id,
            'target_role' => UserRole::DEVELOPER->value,
            'first_name' => 'Nina',
            'last_name' => 'Petit',
            'email' => 'nina.petit@technova.fr',
            'created_by_user_id' => $lead->id,
        ]);
    }
}


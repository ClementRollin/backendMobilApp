<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::query()->where('slug', 'technova')->firstOrFail();

        $users = [
            [
                'first_name' => 'Claire',
                'last_name' => 'Martin',
                'name' => 'Claire Martin',
                'email' => 'cto@technova.fr',
                'role' => UserRole::CTO->value,
            ],
            [
                'first_name' => 'Lucas',
                'last_name' => 'Bernard',
                'name' => 'Lucas Bernard',
                'email' => 'lead@technova.fr',
                'role' => UserRole::LEAD_DEV->value,
            ],
            [
                'first_name' => 'Julie',
                'last_name' => 'Moreau',
                'name' => 'Julie Moreau',
                'email' => 'dev@technova.fr',
                'role' => UserRole::DEVELOPER->value,
            ],
            [
                'first_name' => 'Thomas',
                'last_name' => 'Petit',
                'name' => 'Thomas Petit',
                'email' => 'po@technova.fr',
                'role' => UserRole::PO->value,
            ],
        ];

        foreach ($users as $user) {
            User::query()->updateOrCreate(
                ['email' => $user['email']],
                [
                    'organization_id' => $organization->id,
                    'role' => $user['role'],
                    'first_name' => $user['first_name'],
                    'last_name' => $user['last_name'],
                    'name' => $user['name'],
                    'password' => Hash::make('password123'),
                ]
            );
        }
    }
}

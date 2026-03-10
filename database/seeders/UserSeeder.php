<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            ['name' => 'Alice Martin', 'email' => 'alice@example.com'],
            ['name' => 'Bob Leroy', 'email' => 'bob@example.com'],
            ['name' => 'Chloe Dubois', 'email' => 'chloe@example.com'],
        ];

        foreach ($users as $user) {
            User::query()->updateOrCreate(
                ['email' => $user['email']],
                [
                    'name' => $user['name'],
                    'password' => Hash::make('password123'),
                ]
            );
        }
    }
}

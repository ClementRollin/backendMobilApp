<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            OrganizationSeeder::class,
            UserSeeder::class,
            TeamSeeder::class,
            TaskSeeder::class,
            CommentSeeder::class,
            TagSeeder::class,
            TaskLinkSeeder::class,
            TaskStatusHistorySeeder::class,
            InvitationCodeSeeder::class,
            DevicePushTokenSeeder::class,
        ]);
    }
}

<?php

namespace Database\Seeders;

use App\Models\DevicePushToken;
use App\Models\User;
use Illuminate\Database\Seeder;

class DevicePushTokenSeeder extends Seeder
{
    public function run(): void
    {
        $developer = User::query()->where('email', 'dev@technova.fr')->firstOrFail();

        DevicePushToken::query()->updateOrCreate(
            ['expo_push_token' => 'ExponentPushToken[technova-demo-token]'],
            [
                'organization_id' => $developer->organization_id,
                'user_id' => $developer->id,
                'platform' => 'android',
                'last_seen_at' => now(),
            ]
        );
    }
}


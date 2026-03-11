<?php

namespace Database\Seeders;

use App\Models\Organization;
use Illuminate\Database\Seeder;

class OrganizationSeeder extends Seeder
{
    public function run(): void
    {
        Organization::query()->updateOrCreate(
            ['slug' => 'technova'],
            [
                'name' => 'TechNova',
            ]
        );
    }
}


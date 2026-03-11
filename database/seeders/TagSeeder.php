<?php

namespace Database\Seeders;

use App\Models\Tag;
use App\Models\Task;
use Illuminate\Database\Seeder;

class TagSeeder extends Seeder
{
    public function run(): void
    {
        $taskApiAuth = Task::query()->where('title', 'Finaliser API Auth')->firstOrFail();
        $taskKanban = Task::query()->where('title', 'Mettre en place le Kanban mobile')->firstOrFail();
        $taskTests = Task::query()->where('title', 'Stabiliser la campagne de tests')->firstOrFail();

        $organizationId = $taskApiAuth->organization_id;

        $bugTag = Tag::query()->updateOrCreate(
            ['organization_id' => $organizationId, 'name' => 'bug'],
            ['color' => '#EF4444']
        );
        $backendTag = Tag::query()->updateOrCreate(
            ['organization_id' => $organizationId, 'name' => 'backend'],
            ['color' => '#2563EB']
        );
        $mobileTag = Tag::query()->updateOrCreate(
            ['organization_id' => $organizationId, 'name' => 'mobile'],
            ['color' => '#10B981']
        );

        $taskApiAuth->tags()->syncWithoutDetaching([$backendTag->id, $bugTag->id]);
        $taskKanban->tags()->syncWithoutDetaching([$mobileTag->id]);
        $taskTests->tags()->syncWithoutDetaching([$bugTag->id]);
    }
}


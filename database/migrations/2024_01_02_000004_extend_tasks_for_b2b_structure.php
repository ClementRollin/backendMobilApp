<?php

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->foreignId('organization_id')
                ->nullable()
                ->constrained('organizations')
                ->cascadeOnDelete();
            $table->foreignId('team_id')
                ->nullable()
                ->constrained('teams')
                ->restrictOnDelete();
            $table->text('blocked_reason')->nullable();
            $table->timestampTz('blocked_confirmed_at')->nullable();
            $table->foreignId('blocked_confirmed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestampTz('deployed_at')->nullable();
        });

        DB::statement('DROP INDEX IF EXISTS tasks_creator_id_status_index');
        DB::statement('DROP INDEX IF EXISTS tasks_assignee_id_status_index');

        if (! Schema::hasColumn('tasks', 'priority')) {
            Schema::table('tasks', function (Blueprint $table) {
                $table->string('priority', 20)->default(TaskPriority::MEDIUM->value);
            });
        }

        DB::statement("
            UPDATE tasks AS t
            SET organization_id = u.organization_id
            FROM users AS u
            WHERE t.creator_id = u.id
              AND t.organization_id IS NULL
        ");

        $legacyOrganizationId = DB::table('organizations')->orderBy('id')->value('id');
        if ($legacyOrganizationId === null) {
            $legacyOrganizationId = DB::table('organizations')->insertGetId([
                'name' => 'Legacy Organization',
                'slug' => 'legacy-organization',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('tasks')
            ->whereNull('organization_id')
            ->update(['organization_id' => $legacyOrganizationId]);

        $organizations = DB::table('organizations')->select('id')->get();
        foreach ($organizations as $organization) {
            DB::table('teams')->updateOrInsert(
                [
                    'organization_id' => $organization->id,
                    'name' => 'Equipe Legacy',
                ],
                [
                    'description' => 'Equipe de secours pour le backfill des donnees legacy.',
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        DB::statement("
            UPDATE tasks AS t
            SET team_id = tm.id
            FROM teams AS tm
            WHERE tm.organization_id = t.organization_id
              AND tm.name = 'Equipe Legacy'
              AND t.team_id IS NULL
        ");

        DB::statement("UPDATE tasks SET status = 'tested' WHERE status = 'done'");

        $statusList = implode(
            ',',
            array_map(static fn (string $status) => "'{$status}'", TaskStatus::values())
        );
        $priorityList = implode(
            ',',
            array_map(static fn (string $priority) => "'{$priority}'", TaskPriority::values())
        );

        DB::statement("
            UPDATE tasks
            SET status = '".TaskStatus::TODO->value."'
            WHERE status IS NULL
               OR status NOT IN ({$statusList})
        ");

        DB::statement("
            UPDATE tasks
            SET priority = '".TaskPriority::MEDIUM->value."'
            WHERE priority IS NULL
               OR priority NOT IN ({$priorityList})
        ");

        DB::statement('ALTER TABLE tasks ALTER COLUMN organization_id SET NOT NULL');
        DB::statement('ALTER TABLE tasks ALTER COLUMN team_id SET NOT NULL');

        DB::statement('ALTER TABLE tasks DROP CONSTRAINT IF EXISTS tasks_status_allowed_chk');
        DB::statement('ALTER TABLE tasks DROP CONSTRAINT IF EXISTS tasks_priority_allowed_chk');
        DB::statement("ALTER TABLE tasks ADD CONSTRAINT tasks_status_allowed_chk CHECK (status IN ({$statusList}))");
        DB::statement("ALTER TABLE tasks ADD CONSTRAINT tasks_priority_allowed_chk CHECK (priority IN ({$priorityList}))");

        Schema::table('tasks', function (Blueprint $table) {
            $table->index('organization_id', 'tasks_organization_id_idx');
            $table->index('team_id', 'tasks_team_id_idx');
            $table->index('creator_id', 'tasks_creator_id_idx');
            $table->index('assignee_id', 'tasks_assignee_id_idx');
            $table->index('status', 'tasks_status_idx');
            $table->index('priority', 'tasks_priority_idx');
            $table->index('deployed_at', 'tasks_deployed_at_idx');
        });
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS tasks_deployed_at_idx');
        DB::statement('DROP INDEX IF EXISTS tasks_priority_idx');
        DB::statement('DROP INDEX IF EXISTS tasks_status_idx');
        DB::statement('DROP INDEX IF EXISTS tasks_assignee_id_idx');
        DB::statement('DROP INDEX IF EXISTS tasks_creator_id_idx');
        DB::statement('DROP INDEX IF EXISTS tasks_team_id_idx');
        DB::statement('DROP INDEX IF EXISTS tasks_organization_id_idx');

        DB::statement('ALTER TABLE tasks DROP CONSTRAINT IF EXISTS tasks_status_allowed_chk');
        DB::statement('ALTER TABLE tasks DROP CONSTRAINT IF EXISTS tasks_priority_allowed_chk');
        DB::statement('ALTER TABLE tasks DROP CONSTRAINT IF EXISTS tasks_blocked_confirmed_by_foreign');
        DB::statement('ALTER TABLE tasks DROP CONSTRAINT IF EXISTS tasks_team_id_foreign');
        DB::statement('ALTER TABLE tasks DROP CONSTRAINT IF EXISTS tasks_organization_id_foreign');

        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn([
                'organization_id',
                'team_id',
                'blocked_reason',
                'blocked_confirmed_at',
                'blocked_confirmed_by',
                'deployed_at',
            ]);
        });

        DB::statement('CREATE INDEX IF NOT EXISTS tasks_creator_id_status_index ON tasks (creator_id, status)');
        DB::statement('CREATE INDEX IF NOT EXISTS tasks_assignee_id_status_index ON tasks (assignee_id, status)');
    }
};


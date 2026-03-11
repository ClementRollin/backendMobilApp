<?php

use App\Enums\UserRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('organization_id')
                ->nullable()
                ->constrained('organizations')
                ->cascadeOnDelete();
            $table->string('role', 20)->default(UserRole::DEVELOPER->value);
            $table->string('first_name', 120)->nullable();
            $table->string('last_name', 120)->nullable();

            $table->index('organization_id', 'users_organization_id_idx');
            $table->index(['organization_id', 'role'], 'users_org_role_idx');
        });

        $legacyOrganizationId = DB::table('organizations')->orderBy('id')->value('id');
        if ($legacyOrganizationId === null) {
            $legacyOrganizationId = DB::table('organizations')->insertGetId([
                'name' => 'Legacy Organization',
                'slug' => 'legacy-organization',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('users')
            ->whereNull('organization_id')
            ->update(['organization_id' => $legacyOrganizationId]);

        DB::statement("
            UPDATE users
            SET first_name = COALESCE(first_name, NULLIF(split_part(name, ' ', 1), '')),
                last_name = COALESCE(last_name, NULLIF(trim(regexp_replace(name, '^\\S+\\s*', '')), ''))
        ");

        DB::table('users')->whereNull('role')->update(['role' => UserRole::DEVELOPER->value]);
        DB::statement('ALTER TABLE users ALTER COLUMN organization_id SET NOT NULL');

        DB::statement("
            CREATE UNIQUE INDEX users_one_cto_per_organization_idx
            ON users (organization_id)
            WHERE role = 'cto'
        ");
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS users_one_cto_per_organization_idx');
        DB::statement('DROP INDEX IF EXISTS users_org_role_idx');
        DB::statement('DROP INDEX IF EXISTS users_organization_id_idx');
        DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_organization_id_foreign');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'organization_id',
                'role',
                'first_name',
                'last_name',
            ]);
        });
    }
};


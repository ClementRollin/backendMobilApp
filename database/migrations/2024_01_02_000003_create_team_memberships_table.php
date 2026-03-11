<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('team_memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['team_id', 'user_id'], 'team_memberships_team_user_unique');
            $table->index('organization_id', 'team_memberships_organization_id_idx');
            $table->index('team_id', 'team_memberships_team_id_idx');
            $table->index('user_id', 'team_memberships_user_id_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_memberships');
    }
};


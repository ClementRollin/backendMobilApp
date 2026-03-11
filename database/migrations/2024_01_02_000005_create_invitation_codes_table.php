<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('invitation_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 100)->unique();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('team_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->string('target_role', 20);
            $table->string('first_name', 120);
            $table->string('last_name', 120);
            $table->string('email', 190);
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('used_at')->nullable();
            $table->timestampTz('revoked_at')->nullable();
            $table->timestamps();

            $table->index('organization_id', 'invitation_codes_organization_id_idx');
            $table->index('team_id', 'invitation_codes_team_id_idx');
            $table->index('created_by_user_id', 'invitation_codes_created_by_idx');
            $table->index('target_role', 'invitation_codes_target_role_idx');
            $table->index('email', 'invitation_codes_email_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invitation_codes');
    }
};


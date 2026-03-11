<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('device_push_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('expo_push_token', 255)->unique();
            $table->string('platform', 20);
            $table->timestampTz('last_seen_at')->nullable();
            $table->timestamps();

            $table->index('organization_id', 'device_push_tokens_organization_id_idx');
            $table->index('user_id', 'device_push_tokens_user_id_idx');
            $table->index('platform', 'device_push_tokens_platform_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_push_tokens');
    }
};


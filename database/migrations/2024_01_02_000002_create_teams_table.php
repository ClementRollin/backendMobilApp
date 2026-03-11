<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->string('name', 140);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'name'], 'teams_org_name_unique');
            $table->index('organization_id', 'teams_organization_id_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teams');
    }
};


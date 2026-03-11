<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('task_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('task_low_id')->constrained('tasks')->cascadeOnDelete();
            $table->foreignId('task_high_id')->constrained('tasks')->cascadeOnDelete();
            $table->string('link_type', 40)->nullable();
            $table->timestamps();

            $table->unique(['task_low_id', 'task_high_id'], 'task_links_low_high_unique');
            $table->index('organization_id', 'task_links_organization_id_idx');
            $table->index('task_low_id', 'task_links_task_low_id_idx');
            $table->index('task_high_id', 'task_links_task_high_id_idx');
        });

        DB::statement('ALTER TABLE task_links ADD CONSTRAINT task_links_order_chk CHECK (task_low_id < task_high_id)');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE task_links DROP CONSTRAINT IF EXISTS task_links_order_chk');
        Schema::dropIfExists('task_links');
    }
};


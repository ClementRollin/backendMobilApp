<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('task_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('old_status', 30)->nullable();
            $table->string('new_status', 30);
            $table->text('comment')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('organization_id', 'task_status_histories_organization_id_idx');
            $table->index('task_id', 'task_status_histories_task_id_idx');
            $table->index('user_id', 'task_status_histories_user_id_idx');
            $table->index('new_status', 'task_status_histories_new_status_idx');
            $table->index('created_at', 'task_status_histories_created_at_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_status_histories');
    }
};


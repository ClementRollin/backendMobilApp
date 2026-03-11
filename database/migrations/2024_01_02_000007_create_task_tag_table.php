<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('task_tag', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained('tags')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['task_id', 'tag_id'], 'task_tag_task_tag_unique');
            $table->index('task_id', 'task_tag_task_id_idx');
            $table->index('tag_id', 'task_tag_tag_id_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_tag');
    }
};


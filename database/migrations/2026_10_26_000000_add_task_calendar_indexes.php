<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('task_histories', function (Blueprint $table) {
            $table->index('created_at', 'task_histories_created_at_index');
            $table->index(['task_id', 'created_at'], 'task_histories_task_id_created_at_index');
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->index(['user_id', 'status'], 'tasks_user_id_status_index');
        });
    }

    public function down(): void
    {
        Schema::table('task_histories', function (Blueprint $table) {
            $table->dropIndex('task_histories_created_at_index');
            $table->dropIndex('task_histories_task_id_created_at_index');
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->dropIndex('tasks_user_id_status_index');
        });
    }
};

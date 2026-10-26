<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->date('planned_on')->nullable()->after('status');
            $table->index(['user_id', 'planned_on'], 'tasks_user_id_planned_on_index');
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropIndex('tasks_user_id_planned_on_index');
            $table->dropColumn('planned_on');
        });
    }
};

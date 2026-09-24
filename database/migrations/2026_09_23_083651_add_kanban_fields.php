<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->unsignedBigInteger('board_version')->default(0);
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->unsignedInteger('kanban_order')->default(0);

            $table->index(
                ['project_id', 'status', 'kanban_order'],
                'tasks_kanban_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropIndex('tasks_kanban_index');
            $table->dropColumn('kanban_order');
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('board_version');
        });
    }
};
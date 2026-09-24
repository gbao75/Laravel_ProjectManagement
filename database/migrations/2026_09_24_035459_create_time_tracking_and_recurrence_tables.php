<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_time_entries', function (Blueprint $table) {
            $table->id();

            $table->foreignId('task_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();

            $table->unsignedBigInteger('duration_seconds')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'ended_at']);
        });

        Schema::create('task_recurrences', function (Blueprint $table) {
            $table->id();

            $table->foreignId('task_id')
                ->unique()
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('frequency', 20);
            $table->date('next_run_on');

            // Giữ ngày mong muốn khi lặp theo tháng, ví dụ ngày 31.
            $table->unsignedTinyInteger('anchor_day');

            $table->unsignedSmallInteger('due_after_days')->default(0);

            $table->boolean('is_active')->default(true);
            $table->string('paused_reason')->nullable();

            $table->timestamps();

            $table->index(['is_active', 'next_run_on']);
        });

        Schema::create('task_recurrence_runs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('task_recurrence_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->date('scheduled_on');

            $table->foreignId('generated_task_id')
                ->nullable()
                ->constrained('tasks')
                ->nullOnDelete();

            $table->timestamps();

            $table->unique(
                ['task_recurrence_id', 'scheduled_on'],
                'task_recurrence_run_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_recurrence_runs');
        Schema::dropIfExists('task_recurrences');
        Schema::dropIfExists('task_time_entries');
    }
};
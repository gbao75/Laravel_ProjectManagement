<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->json('notification_preferences')->nullable();
        });

        Schema::create('task_reminder_deliveries', function (Blueprint $table) {
            $table->id();

            $table->foreignId('task_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->date('due_date');
            $table->string('kind', 30);
            $table->timestamp('sent_at');

            $table->unique(
                ['task_id', 'user_id', 'due_date', 'kind'],
                'task_reminder_delivery_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_reminder_deliveries');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('notification_preferences');
        });
    }
};
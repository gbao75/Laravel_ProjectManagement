<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->unsignedBigInteger('project_id')
                ->nullable()
                ->change();
        });
    }

    public function down(): void
    {
        if (DB::table('tasks')->whereNull('project_id')->exists()) {
            throw new RuntimeException(
                'Không thể rollback khi còn công việc cá nhân.'
            );
        }

        Schema::table('tasks', function (Blueprint $table) {
            $table->unsignedBigInteger('project_id')
                ->nullable(false)
                ->change();
        });
    }
};
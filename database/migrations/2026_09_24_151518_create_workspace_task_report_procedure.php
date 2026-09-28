<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::unprepared(<<<'WORKSPACE_REPORT_SQL'
CREATE PROCEDURE workspace_task_report(
    IN p_workspace_id BIGINT UNSIGNED,
    IN p_today DATE
)
SQL SECURITY INVOKER
BEGIN
    SELECT
        p.id AS project_id,
        p.name AS project_name,

        COUNT(t.id) AS total_tasks,

        SUM(
            CASE WHEN t.status = 'todo'
            THEN 1 ELSE 0 END
        ) AS todo_count,

        SUM(
            CASE WHEN t.status = 'in_progress'
            THEN 1 ELSE 0 END
        ) AS in_progress_count,

        SUM(
            CASE WHEN t.status = 'completed'
            THEN 1 ELSE 0 END
        ) AS completed_count,

        SUM(
            CASE
                WHEN t.status <> 'completed'
                    AND t.due_date < p_today
                THEN 1 ELSE 0
            END
        ) AS overdue_count

    FROM projects p
    LEFT JOIN tasks t ON t.project_id = p.id

    WHERE p.workspace_id = p_workspace_id

    GROUP BY p.id, p.name
    ORDER BY p.name, p.id;
END
WORKSPACE_REPORT_SQL);
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::unprepared(
            'DROP PROCEDURE IF EXISTS workspace_task_report'
        );
    }
};
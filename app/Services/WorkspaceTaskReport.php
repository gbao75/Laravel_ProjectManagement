<?php

namespace App\Services;

use App\Models\Workspace;
use Illuminate\Support\Facades\DB;
use PDO;

class WorkspaceTaskReport
{
    public function get(Workspace $workspace): array
    {
        $today = now(
            config('task_reminders.timezone', 'Asia/Ho_Chi_Minh')
        )->toDateString();

        if (DB::connection()->getDriverName() === 'mysql') {
            $rows = $this->fromProcedure($workspace, $today);
        } else {
            // Nhánh tương đương để test bằng SQLite.
            $rows = $this->fromQuery($workspace, $today);
        }

        return array_map(function ($row) {
            $row = (array) $row;

            foreach ([
                'project_id',
                'total_tasks',
                'todo_count',
                'in_progress_count',
                'completed_count',
                'overdue_count',
            ] as $field) {
                $row[$field] = (int) $row[$field];
            }

            $row['completion_percent'] = $row['total_tasks'] > 0
                ? round(
                    $row['completed_count'] * 100 / $row['total_tasks'],
                    1
                )
                : 0;

            return $row;
        }, $rows);
    }

    private function fromProcedure(
        Workspace $workspace,
        string $today
    ): array {
        $statement = DB::connection()
            ->getPdo()
            ->prepare('CALL workspace_task_report(?, ?)');

        try {
            $statement->execute([
                $workspace->id,
                $today,
            ]);

            return $statement->fetchAll(PDO::FETCH_ASSOC);
        } finally {
            $statement->closeCursor();
        }
    }

    private function fromQuery(
        Workspace $workspace,
        string $today
    ): array {
        return DB::table('projects as p')
            ->leftJoin('tasks as t', function ($join) {
                $join->on('t.project_id', '=', 'p.id')
                    ->whereNull('t.deleted_at');
            })
            ->where('p.workspace_id', $workspace->id)
            ->select('p.id as project_id', 'p.name as project_name')
            ->selectRaw('COUNT(t.id) AS total_tasks')
            ->selectRaw(
                "SUM(CASE WHEN t.status = 'todo' THEN 1 ELSE 0 END) AS todo_count"
            )
            ->selectRaw(
                "SUM(CASE WHEN t.status = 'in_progress' THEN 1 ELSE 0 END) AS in_progress_count"
            )
            ->selectRaw(
                "SUM(CASE WHEN t.status = 'completed' THEN 1 ELSE 0 END) AS completed_count"
            )
            ->selectRaw(
                "SUM(CASE WHEN t.status <> 'completed' AND t.due_date < ? THEN 1 ELSE 0 END) AS overdue_count",
                [$today]
            )
            ->groupBy('p.id', 'p.name')
            ->orderBy('p.name')
            ->orderBy('p.id')
            ->get()
            ->all();
    }
}
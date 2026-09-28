<?php

namespace App\Http\Controllers;

use App\Models\Workspace;
use App\Services\WorkspaceTaskReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WorkspaceReportController extends Controller
{
    public function index(
        Workspace $workspace,
        WorkspaceTaskReport $report
    ): View {
        Gate::authorize('update', $workspace);

        $rows = $report->get($workspace);

        return view('workspaces.reports.index', compact(
            'workspace',
            'rows'
        ));
    }

    public function data(
        Workspace $workspace,
        WorkspaceTaskReport $report
    ): JsonResponse {
        Gate::authorize('update', $workspace);

        return response()->json([
            'data' => $report->get($workspace),
        ]);
    }

    public function export(
        Workspace $workspace,
        WorkspaceTaskReport $report
    ): StreamedResponse {
        Gate::authorize('update', $workspace);

        $rows = $report->get($workspace);

        $filename = 'workspace-'.$workspace->id
            .'-report-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($rows) {
            $output = fopen('php://output', 'w');

            // UTF-8 BOM để Excel nhận tiếng Việt thuận tiện hơn.
            fwrite($output, "\xEF\xBB\xBF");

            fputcsv($output, [
                'ID dự án',
                'Dự án',
                'Tổng công việc',
                'Chưa làm',
                'Đang thực hiện',
                'Hoàn thành',
                'Quá hạn',
                'Tỷ lệ hoàn thành (%)',
            ], ',', '"', '');

            foreach ($rows as $row) {
                fputcsv($output, [
                    $row['project_id'],
                    $this->safeCsvText($row['project_name']),
                    $row['total_tasks'],
                    $row['todo_count'],
                    $row['in_progress_count'],
                    $row['completed_count'],
                    $row['overdue_count'],
                    $row['completion_percent'],
                ], ',', '"', '');
            }

            fclose($output);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store',
        ]);
    }

    private function safeCsvText(string $value): string
    {
        // Tránh để tên dự án bị Excel hiểu là công thức.
        if (
            preg_match('/^[\s\x{FEFF}]*[=+\-@]/u', $value)
            || preg_match('/^[\t\r\n]/', $value)
        ) {
            return "'".$value;
        }

        return $value;
    }
}
@extends('layouts.app')

@section('title', 'Báo cáo workspace')
@section('page-title', 'Báo cáo workspace')

@section('content')
    @php
        $totalTasks = array_sum(array_column($rows, 'total_tasks'));
        $completedTasks = array_sum(array_column($rows, 'completed_count'));
        $overdueTasks = array_sum(array_column($rows, 'overdue_count'));
    @endphp

    <div class="space-y-6">
        <header class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold">Báo cáo công việc</h1>
                <p class="mt-2 text-slate-500">{{ $workspace->name }}</p>
            </div>

            <a
                href="{{ route('workspaces.reports.export', $workspace) }}"
                class="rounded-lg bg-indigo-600 px-4 py-2 font-semibold text-white"
            >
                Xuất CSV
            </a>
        </header>

        <div class="grid gap-4 sm:grid-cols-3">
            @foreach ([
                'Tổng công việc' => $totalTasks,
                'Đã hoàn thành' => $completedTasks,
                'Chưa xong và quá hạn' => $overdueTasks,
            ] as $label => $value)
                <article class="rounded-2xl border border-slate-200 bg-white p-5">
                    <p class="text-slate-500">{{ $label }}</p>
                    <p class="mt-3 text-3xl font-bold">{{ $value }}</p>
                </article>
            @endforeach
        </div>

        <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white">
            <table class="w-full text-left">
                <thead class="bg-slate-50 text-sm text-slate-500">
                    <tr>
                        <th class="p-4">Dự án</th>
                        <th class="p-4">Tổng</th>
                        <th class="p-4">Chưa làm</th>
                        <th class="p-4">Đang làm</th>
                        <th class="p-4">Hoàn thành</th>
                        <th class="p-4">Quá hạn</th>
                        <th class="p-4">Tỷ lệ</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($rows as $row)
                        <tr class="border-t border-slate-200">
                            <td class="p-4 font-semibold">
                                {{ $row['project_name'] }}
                            </td>

                            <td class="p-4">{{ $row['total_tasks'] }}</td>
                            <td class="p-4">{{ $row['todo_count'] }}</td>
                            <td class="p-4">{{ $row['in_progress_count'] }}</td>
                            <td class="p-4">{{ $row['completed_count'] }}</td>

                            <td class="p-4 text-rose-600">
                                {{ $row['overdue_count'] }}
                            </td>

                            <td class="p-4">
                                {{ $row['completion_percent'] }}%
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-6 text-center text-slate-500">
                                Workspace chưa có dự án.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
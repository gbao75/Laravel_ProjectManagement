@extends('layouts.app')

@section('title', 'Công việc dự án')
@section('page-title', 'Công việc dự án')

@section('content')
    <div class="task-page">
        <div class="task-heading">
            <div>
                <h1>Công việc</h1>
                <p>{{ $project->name }}</p>
            </div>

            <div class="task-actions">
                <a href="{{ route('workspaces.projects.show', [
                    'workspace' => $workspace,
                    'project' => $project,
                ]) }}" class="primary-button">
                    ← Quay lại dự án
                </a>

                @can('create', [\App\Models\Task::class, $project])
                    <a
                        class="task-primary"
                        href="{{ route('workspaces.projects.tasks.create', [
                            'workspace' => $workspace,
                            'project' => $project,
                        ]) }}"
                    >
                        + Tạo công việc
                    </a>
                @endcan
            </div>
        </div>

        @if (session('status'))
            <div class="task-notice" role="status">
                {{ session('status') }}
            </div>
        @endif

        <section class="task-panel">
            <div class="task-table-scroll">
                <table class="task-table">
                    <thead>
                        <tr>
                            <th>Công việc</th>
                            <th>Người thực hiện</th>
                            <th>Ưu tiên</th>
                            <th>Trạng thái</th>
                            <th>Hạn hoàn thành</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($tasks as $task)
                            <tr>
                                <td class="task-title-cell">
                                    <strong>{{ $task->title }}</strong>

                                    <small>
                                        Tạo bởi:
                                        {{ $task->creator?->name ?? 'Tài khoản đã xóa' }}
                                    </small>

                                    @if ($task->description)
                                        <details class="task-description">
                                            <summary>Xem mô tả</summary>
                                            <p>{{ $task->description }}</p>
                                        </details>
                                    @endif
                                </td>

                                <td>
                                    {{ $task->assignee?->name ?? 'Chưa giao' }}
                                </td>

                                <td>
                                    <span class="task-badge priority-{{ $task->priority }}">
                                        {{ \App\Models\Task::PRIORITIES[$task->priority] ?? $task->priority }}
                                    </span>
                                </td>

                                <td>
                                    <span class="task-badge">
                                        {{ \App\Models\Task::STATUSES[$task->status] ?? $task->status }}
                                    </span>
                                </td>

                                <td>
                                    {{ $task->due_date?->format('d/m/Y') ?? 'Chưa đặt hạn' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="task-empty">
                                    Dự án chưa có công việc.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($tasks->hasPages())
                <div class="task-pagination">
                    {{ $tasks->links() }}
                </div>
            @endif
        </section>
    </div>
@endsection
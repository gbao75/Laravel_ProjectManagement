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
                ]) }}"
                class="primary-button">
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

                <a href="{{ route('workspaces.projects.board.index', [
                    'workspace' => $workspace,
                    'project' => $project,
                ]) }}"
                class="primary-button">
                    Xem Kanban
                </a>
            </div>
        </div>

        {{-- Thông báo thành công --}}
        @if (session('status'))
            <div class="task-notice" role="status">
                {{ session('status') }}
            </div>
        @endif

        {{-- Lỗi validation khi cập nhật trạng thái --}}
        @if ($errors->any())
            <div class="task-errors" role="alert">
                <strong>Vui lòng kiểm tra thông tin:</strong>

                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <section class="task-panel">
            <div class="task-table-scroll">
                <table class="task-table">
                    <thead>
                        <tr>
                            <th scope="col">Công việc</th>
                            <th scope="col">Người thực hiện</th>
                            <th scope="col">Ưu tiên</th>
                            <th scope="col">Trạng thái</th>
                            <th scope="col">Hạn hoàn thành</th>
                            <th scope="col">Thao tác</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($tasks as $task)
                            <tr>
                                {{-- Tiêu đề, người tạo và mô tả --}}
                                <td class="task-title-cell">
                                    <strong>
                                        <a
                                            href="{{ route('workspaces.projects.tasks.show', [
                                                'workspace' => $workspace,
                                                'project' => $project,
                                                'task' => $task,
                                            ]) }}"
                                            class="font-semibold text-indigo-600 hover:underline"
                                        >
                                            {{ $task->title }}
                                        </a></strong>

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

                                {{-- Người thực hiện --}}
                                <td>
                                    {{ $task->assignee?->name ?? 'Chưa giao' }}
                                </td>

                                {{-- Độ ưu tiên --}}
                                <td>
                                    <span class="task-badge priority-{{ $task->priority }}">
                                        {{ \App\Models\Task::PRIORITIES[$task->priority] ?? $task->priority }}
                                    </span>
                                </td>

                                {{-- Trạng thái và form cập nhật --}}
                                <td>
                                    @can('updateStatus', $task)
                                        <form
                                            method="POST"
                                            action="{{ route('workspaces.projects.tasks.status.update', [
                                                'workspace' => $workspace,
                                                'project' => $project,
                                                'task' => $task,
                                            ]) }}"
                                            class="task-status-form"
                                        >
                                            @csrf
                                            @method('PATCH')

                                            <label
                                                class="task-visually-hidden"
                                                for="status-{{ $task->id }}"
                                            >
                                                Trạng thái của {{ $task->title }}
                                            </label>

                                            <select
                                                id="status-{{ $task->id }}"
                                                name="status"
                                                required
                                            >
                                                @foreach (\App\Models\Task::STATUSES as $value => $label)
                                                    <option
                                                        value="{{ $value }}"
                                                        @selected($task->status === $value)
                                                    >
                                                        {{ $label }}
                                                    </option>
                                                @endforeach
                                            </select>

                                            <button
                                                type="submit"
                                                class="task-save-status"
                                                aria-label="Lưu trạng thái của {{ $task->title }}"
                                            >
                                                Lưu
                                            </button>
                                        </form>
                                    @else
                                        <span class="task-badge task-state-{{ $task->status }}">
                                            {{ \App\Models\Task::STATUSES[$task->status] ?? $task->status }}
                                        </span>
                                    @endcan
                                </td>

                                {{-- Hạn hoàn thành --}}
                                <td>
                                    {{ $task->due_date?->format('d/m/Y') ?? 'Chưa đặt hạn' }}
                                </td>

                                {{-- Sửa và xóa --}}
                                <td>
                                    <div class="task-row-actions">
                                        @can('update', $task)
                                            <a
                                                class="task-edit-button"
                                                href="{{ route('workspaces.projects.tasks.edit', [
                                                    'workspace' => $workspace,
                                                    'project' => $project,
                                                    'task' => $task,
                                                ]) }}"
                                                aria-label="Sửa công việc {{ $task->title }}"
                                            >
                                                Sửa
                                            </a>
                                        @endcan

                                        @can('delete', $task)
                                            <form
                                                method="POST"
                                                action="{{ route('workspaces.projects.tasks.destroy', [
                                                    'workspace' => $workspace,
                                                    'project' => $project,
                                                    'task' => $task,
                                                ]) }}"
                                                onsubmit="return confirm('Xóa vĩnh viễn công việc này? Thao tác không thể hoàn tác.');"
                                            >
                                                @csrf
                                                @method('DELETE')

                                                <button
                                                    type="submit"
                                                    class="task-delete-button"
                                                    aria-label="Xóa công việc {{ $task->title }}"
                                                >
                                                    Xóa
                                                </button>
                                            </form>
                                        @endcan

                                        @cannot('update', $task)
                                            @cannot('delete', $task)
                                                <span>—</span>
                                            @endcannot
                                        @endcannot
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="task-empty">
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
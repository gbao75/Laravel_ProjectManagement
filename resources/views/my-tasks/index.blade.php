@extends('layouts.app')

@section('title', 'Công việc của tôi')
@section('page-title', 'Công việc của tôi')

@section('content')
    <div class="task-page my-tasks-page">
        <div class="task-heading">
            <div>
                <h1>Công việc của tôi</h1>
                <p>Việc cá nhân và công việc được giao trong dự án.</p>
            </div>

            <a
                class="task-primary"
                href="{{ route('personal-tasks.create') }}"
            >
                + Việc cá nhân
            </a>
        </div>

        @if (session('status'))
            <div class="task-notice" role="status">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="task-errors" role="alert">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>

                <a href="{{ route('my-tasks.index') }}">Xóa bộ lọc</a>
            </div>
        @endif

        <section class="task-panel">
            <form
                method="GET"
                action="{{ route('my-tasks.index') }}"
                class="my-task-filters"
            >
                <div class="task-field">
                    <label for="q">Tìm công việc</label>
                    <input
                        id="q"
                        name="q"
                        type="search"
                        maxlength="100"
                        value="{{ $filters['q'] }}"
                        placeholder="Nhập tiêu đề..."
                    >
                </div>

                <div class="task-field">
                    <label for="scope">Loại công việc</label>
                    <select id="scope" name="scope">
                        <option value="all" @selected($filters['scope'] === 'all')>
                            Tất cả
                        </option>
                        <option value="personal" @selected($filters['scope'] === 'personal')>
                            Cá nhân
                        </option>
                        <option value="project" @selected($filters['scope'] === 'project')>
                            Dự án
                        </option>
                    </select>
                </div>

                <div class="task-field">
                    <label for="status">Trạng thái</label>
                    <select id="status" name="status">
                        <option value="">Tất cả trạng thái</option>

                        @foreach (\App\Models\Task::STATUSES as $value => $label)
                            <option
                                value="{{ $value }}"
                                @selected($filters['status'] === $value)
                            >
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="task-field">
                    <label for="priority">Độ ưu tiên</label>
                    <select id="priority" name="priority">
                        <option value="">Tất cả mức ưu tiên</option>

                        @foreach (\App\Models\Task::PRIORITIES as $value => $label)
                            <option
                                value="{{ $value }}"
                                @selected($filters['priority'] === $value)
                            >
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="task-actions">
                    <button type="submit" class="task-primary">Áp dụng</button>
                    <a href="{{ route('my-tasks.index') }}">Xóa lọc</a>
                </div>
            </form>
        </section>

        <section class="task-panel">
            <h2 class="saved-filter-title">Bộ lọc đã lưu</h2>

            <p class="saved-filter-note">
                Bấm “Áp dụng” trước khi lưu.
                Dùng lại tên cũ sẽ cập nhật bộ lọc đó.
            </p>

            <form
                method="POST"
                action="{{ route('my-task-filters.store') }}"
                class="saved-filter-form"
            >
                @csrf

                @foreach ($filters as $key => $value)
                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                @endforeach

                <div class="task-field">
                    <label for="filter-name">Tên bộ lọc</label>
                    <input
                        id="filter-name"
                        name="name"
                        maxlength="100"
                        value="{{ old('name') }}"
                        placeholder="Ví dụ: Việc cá nhân ưu tiên cao"
                        required
                    >
                </div>

                <button type="submit" class="task-primary">
                    Lưu bộ lọc hiện tại
                </button>
            </form>

            <div class="saved-filter-list">
                @forelse ($savedFilters as $savedFilter)
                    <div class="saved-filter-item">
                        <a href="{{ route('my-tasks.index', $savedFilter->filters) }}">
                            {{ $savedFilter->name }}
                        </a>

                        <form
                            method="POST"
                            action="{{ route('my-task-filters.destroy', $savedFilter->id) }}"
                            onsubmit="return confirm('Xóa bộ lọc này?');"
                        >
                            @csrf
                            @method('DELETE')

                            <button
                                type="submit"
                                class="task-delete-button"
                                aria-label="Xóa bộ lọc {{ $savedFilter->name }}"
                            >
                                Xóa
                            </button>
                        </form>
                    </div>
                @empty
                    <p class="saved-filter-note">Bạn chưa lưu bộ lọc nào.</p>
                @endforelse
            </div>
        </section>

        <section class="task-panel">
            <div class="my-task-results">
                <h2>Danh sách công việc</h2>
                <span>{{ $tasks->total() }} kết quả</span>
            </div>

            <div class="task-table-scroll">
                <table class="task-table">
                    <thead>
                        <tr>
                            <th scope="col">Công việc</th>
                            <th scope="col">Phạm vi</th>
                            <th scope="col">Ưu tiên</th>
                            <th scope="col">Trạng thái</th>
                            <th scope="col">Hạn</th>
                            <th scope="col">Thao tác</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($tasks as $task)
                            <tr>
                                <td class="task-title-cell">
                                    <strong>{{ $task->title }}</strong>

                                    @if ($task->description)
                                        <details class="task-description">
                                            <summary>Xem mô tả</summary>
                                            <p>{{ $task->description }}</p>
                                        </details>
                                    @endif
                                </td>

                                <td class="my-task-project">
                                    @if ($task->project_id === null)
                                        <span class="task-badge">Cá nhân</span>
                                    @else
                                        <strong>{{ $task->project->name }}</strong>
                                        <small>
                                            {{ $task->project->workspace->name }}
                                        </small>
                                    @endif
                                </td>

                                <td>
                                    <span class="task-badge priority-{{ $task->priority }}">
                                        {{ \App\Models\Task::PRIORITIES[$task->priority] ?? $task->priority }}
                                    </span>
                                </td>

                                <td>
                                    <span class="task-badge task-state-{{ $task->status }}">
                                        {{ \App\Models\Task::STATUSES[$task->status] ?? $task->status }}
                                    </span>
                                </td>

                                <td>
                                    {{ $task->due_date?->format('d/m/Y') ?? 'Chưa đặt hạn' }}
                                </td>

                                <td>
                                    @if ($task->project_id === null)
                                        <div class="task-row-actions">
                                            @can('update', $task)
                                                <a
                                                    class="task-edit-button"
                                                    href="{{ route('personal-tasks.edit', $task) }}"
                                                >
                                                    Sửa
                                                </a>
                                            @endcan

                                            @can('delete', $task)
                                                <form
                                                    method="POST"
                                                    action="{{ route('personal-tasks.destroy', $task) }}"
                                                    onsubmit="return confirm('Xóa vĩnh viễn công việc cá nhân này?');"
                                                >
                                                    @csrf
                                                    @method('DELETE')

                                                    <button
                                                        type="submit"
                                                        class="task-delete-button"
                                                    >
                                                        Xóa
                                                    </button>
                                                </form>
                                            @endcan
                                        </div>
                                    @else
                                        <a
                                            class="task-edit-button"
                                            href="{{ route('workspaces.projects.tasks.index', [
                                                'workspace' => $task->project->workspace,
                                                'project' => $task->project,
                                            ]) }}"
                                        >
                                            Công việc dự án
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="task-empty">
                                    Không có công việc phù hợp.
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
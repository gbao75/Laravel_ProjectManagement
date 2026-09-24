@extends('layouts.app')

@section('title', 'Kanban')
@section('page-title', 'Kanban')

@section('content')
    <div
        class="kanban-page"
        id="project-board"
        data-version="{{ $project->board_version }}"
        data-move-url="{{ route('workspaces.projects.board.move', [
            'workspace' => $workspace,
            'project' => $project,
        ]) }}"
    >
        <div class="kanban-heading">
            <div>
                <h1>Kanban</h1>
                <p>{{ $project->name }}</p>
            </div>

            <a href="{{ route('workspaces.projects.tasks.index', [
                'workspace' => $workspace,
                'project' => $project,
            ]) }}"
            class="primary-button">
                ← Xem dạng danh sách
            </a>
        </div>

        <div class="kanban-feedback">
            <p id="board-message" role="status" aria-live="polite"></p>

            <button type="button" id="board-reload" hidden>
                Tải lại bảng
            </button>
        </div>

        <div class="kanban-columns">
            @foreach (\App\Models\Task::STATUSES as $status => $label)
                <section class="kanban-column">
                    <header class="kanban-column-header">
                        <h2>{{ $label }}</h2>

                        <span data-count-for="{{ $status }}">
                            {{ $tasks->where('status', $status)->count() }}
                        </span>
                    </header>

                    <div
                        class="kanban-list"
                        data-status="{{ $status }}"
                        aria-label="{{ $label }}"
                    >
                        @foreach ($tasks->where('status', $status) as $task)
                            <article
                                class="kanban-card"
                                data-id="{{ $task->id }}"
                                data-can-drag="{{ auth()->user()->can('updateStatus', $task) ? '1' : '0' }}"
                            >
                                <div class="kanban-card-top">
                                    <span class="kanban-priority priority-{{ $task->priority }}">
                                        {{ \App\Models\Task::PRIORITIES[$task->priority] }}
                                    </span>
                                </div>

                                <h3>
                                    <a
                                        href="{{ route('workspaces.projects.tasks.show', [
                                            'workspace' => $workspace,
                                            'project' => $project,
                                            'task' => $task,
                                        ]) }}"
                                    >
                                        {{ $task->title }}
                                    </a>
                                </h3>

                                @if ($task->description)
                                    <details>
                                        <summary>Mô tả</summary>
                                        <p class="kanban-description">{{ $task->description }}</p>
                                    </details>
                                @endif

                                <p class="kanban-meta">
                                    {{ $task->assignee?->name ?? 'Chưa giao' }}
                                </p>

                                <p class="kanban-meta">
                                    Hạn:
                                    {{ $task->due_date?->format('d/m/Y') ?? 'Chưa đặt' }}
                                </p>

                                @can('updateStatus', $task)
                                    <div class="kanban-card-actions">
                                        <button
                                            type="button"
                                            data-action="up"
                                            aria-label="Đưa {{ $task->title }} lên"
                                        >
                                            ↑ Lên
                                        </button>

                                        <button
                                            type="button"
                                            data-action="down"
                                            aria-label="Đưa {{ $task->title }} xuống"
                                        >
                                            ↓ Xuống
                                        </button>
                                    </div>

                                    <form class="kanban-status-form">
                                        <label class="kanban-sr-only" for="board-status-{{ $task->id }}">
                                            Trạng thái {{ $task->title }}
                                        </label>

                                        <select id="board-status-{{ $task->id }}" name="status">
                                            @foreach (\App\Models\Task::STATUSES as $value => $text)
                                                <option
                                                    value="{{ $value }}"
                                                    @selected($task->status === $value)
                                                >
                                                    {{ $text }}
                                                </option>
                                            @endforeach
                                        </select>

                                        <button type="submit">Chuyển</button>
                                    </form>
                                @endcan

                                @can('update', $task)
                                    <a
                                        class="kanban-edit"
                                        href="{{ route('workspaces.projects.tasks.edit', [
                                            'workspace' => $workspace,
                                            'project' => $project,
                                            'task' => $task,
                                        ]) }}"
                                    >
                                        Sửa nội dung
                                    </a>
                                @endcan
                            </article>
                        @endforeach
                    </div>
                </section>
            @endforeach
        </div>
    </div>
@endsection
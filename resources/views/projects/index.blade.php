@extends('layouts.app')

@section('title', 'Dự án')
@section('page-title', 'Dự án workspace')

@section('content')
    @if (session('status'))
        <div class="auth-notice" role="status">
            {{ session('status') }}
        </div>
    @endif

    <div class="workspace-toolbar">
        <div class="page-heading">
            <h1>Dự án</h1>

            <p class="muted">
                {{ $workspace->name }}
                · {{ $projects->total() }} dự án trong kết quả
            </p>
        </div>

        @can('create', [\App\Models\Project::class, $workspace])
            <a
                href="{{ route('workspaces.projects.create', $workspace) }}"
                class="primary-button"
            >
                Tạo dự án
            </a>
        @endcan
    </div>

    <form
        method="GET"
        action="{{ route('workspaces.projects.index', $workspace) }}"
        class="project-filter"
    >
        <label for="filter-status">Trạng thái</label>

        <select id="filter-status" name="status">
            <option value="">Tất cả trạng thái</option>

            @foreach (\App\Models\Project::STATUSES as $value => $label)
                <option value="{{ $value }}" @selected($status === $value)>
                    {{ $label }}
                </option>
            @endforeach
        </select>

        <button type="submit">Lọc</button>

        <a href="{{ route('workspaces.projects.index', $workspace) }}">
            Hủy
        </a>
    </form>

    @error('status')
        <p class="field-error">{{ $message }}</p>
    @enderror

    <div class="project-grid">
        @forelse ($projects as $project)
            <article class="panel project-card">
                <span class="project-status project-status--{{ $project->status }}">
                    {{ $project->statusLabel() }}
                </span>

                <h2>
                    <a href="{{ route('workspaces.projects.show', [
                        'workspace' => $workspace,
                        'project' => $project,
                    ]) }}">
                        {{ $project->name }}
                    </a>
                </h2>

                <p class="muted">
                    {{ \Illuminate\Support\Str::limit(
                        $project->description ?: 'Chưa có mô tả.',
                        140
                    ) }}
                </p>

                <div class="project-card__footer">
                    <span>
                        Hạn:
                        {{ $project->due_date?->format('d/m/Y') ?? 'Chưa đặt' }}
                    </span>

                    <span>
                        {{ $project->creator?->name ?? 'Tài khoản đã xóa' }}
                    </span>
                </div>
            </article>
        @empty
            <section class="panel project-card">
                <h2>Chưa có dự án phù hợp</h2>

                <p class="muted">
                    Tạo dự án mới hoặc thay đổi bộ lọc.
                </p>
            </section>
        @endforelse
    </div>

    @if ($projects->hasPages())
        <nav class="workspace-pagination" aria-label="Phân trang dự án">
            @if ($projects->previousPageUrl())
                <a href="{{ $projects->previousPageUrl() }}">← Trang trước</a>
            @endif

            <span>
                Trang {{ $projects->currentPage() }}
                / {{ $projects->lastPage() }}
            </span>

            @if ($projects->hasMorePages())
                <a href="{{ $projects->nextPageUrl() }}">Trang sau →</a>
            @endif
        </nav>
    @endif

    <p class="members-back-link">
        <a href="{{ route('workspaces.show', $workspace) }}" class="primary-button">
            ← Quay lại workspace
        </a>
    </p>
@endsection
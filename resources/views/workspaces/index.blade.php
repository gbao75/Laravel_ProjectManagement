@extends('layouts.app')

@section('title', 'Workspace')
@section('page-title', 'Workspace')

@section('content')
    @if (session('status'))
        <div class="auth-notice" role="status">
            {{ session('status') }}
        </div>
    @endif
    
    <div class="workspace-toolbar">
        <div class="page-heading">
            <h1>Workspace của bạn</h1>
            <p class="muted">
                Những không gian làm việc bạn đang tham gia.
            </p>
        </div>

        <a href="{{ route('workspaces.create') }}" class="primary-button">
            Tạo workspace
        </a>
    </div>

    <div class="workspace-grid">
        @forelse ($workspaces as $workspace)
            <article class="panel workspace-card">
                <span class="badge">
                    @if ($workspace->owner_id === auth()->id())
                        Chủ sở hữu
                    @elseif ($workspace->pivot->role === 'admin')
                        Quản trị viên
                    @else
                        Thành viên
                    @endif
                </span>

                <h2>
                    <a href="{{ route('workspaces.show', $workspace) }}">
                        {{ $workspace->name }}
                    </a>
                </h2>

                <p class="muted">
                    {{ \Illuminate\Support\Str::limit(
                        $workspace->description ?: 'Chưa có mô tả.',
                        140
                    ) }}
                </p>

                <p>
                    Chủ sở hữu: {{ $workspace->owner->name }}
                </p>

                <p class="muted">
                    {{ $workspace->members_count }} thành viên
                </p>

                <a href="{{ route('workspaces.show', $workspace) }}">
                    Mở workspace →
                </a>
            </article>
        @empty
            <div class="panel workspace-card">
                <h2>Bạn chưa tham gia workspace nào</h2>

                <p class="muted">
                    Tạo workspace đầu tiên để bắt đầu quản lý dự án.
                </p>
            </div>
        @endforelse
    </div>

    @if ($workspaces->hasPages())
        <nav class="workspace-pagination" aria-label="Phân trang workspace">
            @if ($workspaces->previousPageUrl())
                <a href="{{ $workspaces->previousPageUrl() }}">← Trang trước</a>
            @endif

            <span>
                Trang {{ $workspaces->currentPage() }}
                / {{ $workspaces->lastPage() }}
            </span>

            @if ($workspaces->hasMorePages())
                <a href="{{ $workspaces->nextPageUrl() }}">Trang sau →</a>
            @endif
        </nav>
    @endif
@endsection
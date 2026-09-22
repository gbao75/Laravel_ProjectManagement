@extends('layouts.app')

@section('title', $project->name)
@section('page-title', 'Chi tiết dự án')

@section('content')
    @if (session('status'))
        <div class="auth-notice" role="status">
            {{ session('status') }}
        </div>
    @endif

    <div class="workspace-toolbar">
        <div class="page-heading">
            <h1>{{ $project->name }}</h1>
            <p class="muted">{{ $workspace->name }}</p>
        </div>

        <a href="{{ route('workspaces.projects.index', $workspace) }}" class="primary-button">
            ← Danh sách dự án
        </a>
    </div>

    <section class="panel project-detail">
        <span class="project-status project-status--{{ $project->status }}">
            {{ $project->statusLabel() }}
        </span>

        <dl class="workspace-details">
            <div>
                <dt>Người tạo</dt>
                <dd>
                    {{ $project->creator?->name ?? 'Tài khoản đã xóa' }}
                </dd>
            </div>

            <div>
                <dt>Ngày bắt đầu</dt>
                <dd>
                    {{ $project->start_date?->format('d/m/Y') ?? 'Chưa đặt' }}
                </dd>
            </div>

            <div>
                <dt>Hạn hoàn thành</dt>
                <dd>
                    {{ $project->due_date?->format('d/m/Y') ?? 'Chưa đặt' }}
                </dd>
            </div>
        </dl>

        <h2>Mô tả</h2>

        <p class="project-description">{{ $project->description ?: 'Chưa có mô tả.' }}</p>
    </section>

    <div class="workspace-actions">
        @can('update', $project)
            <a
                href="{{ route('workspaces.projects.edit', [
                    'workspace' => $workspace,
                    'project' => $project,
                ]) }}"
                class="primary-button"
            >
                Sửa dự án
            </a>
        @endcan

        <a href="{{ route('workspaces.projects.tasks.index', [
            'workspace' => $workspace,
            'project' => $project,
        ]) }}"
        class="primary-button">
            Xem công việc
        </a>    

        @can('manageMembers', $project)
            <a href="{{ route('workspaces.projects.members.index', [
                'workspace' => $workspace,
                'project' => $project,
            ]) }}"
            class="primary-button">
                Quản lý thành viên
            </a>
        @endcan
    </div>

    @can('delete', $project)
        <section class="panel workspace-danger-zone">
            <h2>Xóa dự án</h2>

            <p>
                Dự án sẽ bị xóa vĩnh viễn và chưa thể khôi phục.
            </p>

            <form
                method="POST"
                action="{{ route('workspaces.projects.destroy', [
                    'workspace' => $workspace,
                    'project' => $project,
                ]) }}"
                onsubmit="return confirm('Bạn chắc chắn muốn xóa vĩnh viễn dự án này?');"
            >
                @csrf
                @method('DELETE')

                <button type="submit" class="danger-button">
                    Xóa dự án
                </button>
            </form>
        </section>
    @endcan
@endsection
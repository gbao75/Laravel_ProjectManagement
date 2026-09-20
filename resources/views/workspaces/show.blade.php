@extends('layouts.app')

@section('title', $workspace->name)
@section('page-title', 'Chi tiết workspace')

@section('content')
    @if (session('status'))
        <div class="auth-notice" role="status">
            {{ session('status') }}
        </div>
    @endif

    <div class="workspace-toolbar">
        <div class="page-heading">
            <h1>{{ $workspace->name }}</h1>
            <p class="muted">Thông tin không gian làm việc.</p>
        </div>

        <a href="{{ route('workspaces.index') }}">
            ← Danh sách workspace
        </a>
    </div>

    <section class="panel workspace-card">
        <h2>Thông tin chung</h2>

        <dl class="workspace-details">
            <div>
                <dt>Chủ sở hữu</dt>
                <dd>{{ $workspace->owner->name }}</dd>
            </div>

            <div>
                <dt>Số thành viên</dt>
                <dd>{{ $workspace->members_count }}</dd>
            </div>

            <div>
                <dt>Ngày tạo</dt>
                <dd>{{ $workspace->created_at->format('d/m/Y') }}</dd>
            </div>

            <div>
                <dt>Múi giờ</dt>
                <dd>{{ $workspace->timezone }}</dd>
            </div>
        </dl>

        <h3>Mô tả</h3>

        <p class="workspace-description">{{ $workspace->description ?: 'Chưa có mô tả.' }}</p>

        <div class="workspace-actions">
            @can('update', $workspace)
                <a
                    href="{{ route('workspaces.edit', $workspace) }}"
                    class="primary-button"
                >
                    Sửa thông tin
                </a>
            @endcan

            @can('invite', $workspace)
                <a
                    href="{{ route('workspaces.invitations.create', $workspace) }}"
                    class="primary-button"
                >
                    Mời thành viên
                </a>
            @endcan
        </div>

        @can('delete', $workspace)
            <section class="panel workspace-danger-zone">
                <h2>Xóa workspace</h2>

                <p>
                    Workspace và danh sách thành viên của workspace sẽ bị xóa
                    vĩnh viễn.
                </p>

                <form
                    method="POST"
                    action="{{ route('workspaces.destroy', $workspace) }}"
                    onsubmit="return confirm('Bạn chắc chắn muốn xóa vĩnh viễn workspace này?');"
                >
                    @csrf
                    @method('DELETE')

                    <button type="submit" class="danger-button">
                        Xóa workspace
                    </button>
                </form>
            </section>
        @endcan
    </section>
@endsection
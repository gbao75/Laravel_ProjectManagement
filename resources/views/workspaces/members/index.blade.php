@extends('layouts.app')

@section('title', 'Thành viên')
@section('page-title', 'Thành viên workspace')

@section('content')
    @if (session('status'))
        <div class="auth-notice" role="status">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="field-error" role="alert">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="workspace-toolbar">
        <div class="page-heading">
            <h1>Thành viên</h1>

            <p class="muted">
                {{ $workspace->name }}
                · {{ $members->total() }} thành viên
            </p>
        </div>

        @can('invite', $workspace)
            <a
                href="{{ route('workspaces.invitations.create', $workspace) }}"
                class="primary-button"
            >
                Mời thành viên
            </a>
        @endcan
    </div>

    <section class="panel members-panel">
        <div class="members-table-wrap">
            <table class="members-table">
                <thead>
                    <tr>
                        <th scope="col">Thành viên</th>
                        <th scope="col">Vai trò</th>
                        <th scope="col">Ngày tham gia</th>
                        <th scope="col">Thao tác</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($members as $member)
                        @php
                            $isOwner = $member->id === $workspace->owner_id;
                        @endphp

                        <tr>
                            <td>
                                <strong>{{ $member->name }}</strong>

                                @if ($member->id === auth()->id())
                                    <span class="member-self">Bạn</span>
                                @endif

                                <div class="member-email">
                                    {{ $member->email }}
                                </div>
                            </td>

                            <td>
                                <span class="member-role">
                                    @if ($isOwner)
                                        Chủ sở hữu
                                    @elseif ($member->pivot->role === 'admin')
                                        Quản trị viên
                                    @else
                                        Thành viên
                                    @endif
                                </span>
                            </td>

                            <td>
                                {{ \Illuminate\Support\Carbon::parse(
                                    $member->pivot->joined_at
                                )->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y') }}
                            </td>

                            <td>
                                @if ($isOwner)
                                    <span class="muted">Chủ sở hữu workspace</span>
                                @else
                                    @can('manageMembers', $workspace)
                                        <div class="member-actions">
                                            <form
                                                method="POST"
                                                action="{{ route('workspaces.members.update', [
                                                    'workspace' => $workspace,
                                                    'member' => $member->id,
                                                ]) }}"
                                                class="member-role-form"
                                            >
                                                @csrf
                                                @method('PUT')

                                                <select
                                                    name="role"
                                                    aria-label="Vai trò của {{ $member->name }}"
                                                >
                                                    <option
                                                        value="member"
                                                        @selected($member->pivot->role === 'member')
                                                    >
                                                        Thành viên
                                                    </option>

                                                    <option
                                                        value="admin"
                                                        @selected($member->pivot->role === 'admin')
                                                    >
                                                        Quản trị viên
                                                    </option>
                                                </select>

                                                <button
                                                    type="submit"
                                                    class="member-save-button"
                                                >
                                                    Lưu
                                                </button>
                                            </form>

                                            <form
                                                method="POST"
                                                action="{{ route('workspaces.members.destroy', [
                                                    'workspace' => $workspace,
                                                    'member' => $member->id,
                                                ]) }}"
                                                onsubmit="return confirm('Xóa thành viên này khỏi workspace?');"
                                            >
                                                @csrf
                                                @method('DELETE')

                                                <button
                                                    type="submit"
                                                    class="member-remove-button"
                                                >
                                                    Xóa khỏi workspace
                                                </button>
                                            </form>
                                        </div>
                                    @else
                                        <span class="muted">—</span>
                                    @endcan
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    @if ($members->hasPages())
        <nav class="workspace-pagination" aria-label="Phân trang thành viên">
            @if ($members->previousPageUrl())
                <a href="{{ $members->previousPageUrl() }}">← Trang trước</a>
            @endif

            <span>
                Trang {{ $members->currentPage() }}
                / {{ $members->lastPage() }}
            </span>

            @if ($members->hasMorePages())
                <a href="{{ $members->nextPageUrl() }}">Trang sau →</a>
            @endif
        </nav>
    @endif

    <p class="members-back-link">
        <a href="{{ route('workspaces.show', $workspace) }}">
            ← Quay lại workspace
        </a>
    </p>
@endsection
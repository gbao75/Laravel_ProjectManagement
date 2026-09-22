@extends('layouts.app')

@section('title', 'Lời mời của tôi')
@section('page-title', 'Lời mời của tôi')

@section('content')
    <div class="workspace-toolbar">
        <div class="page-heading">
            <h1>Lời mời của tôi</h1>

            <p class="muted">
                Lời mời tham gia workspace gửi đến
                {{ auth()->user()->email }}.
            </p>
        </div>

        <span class="my-invitations-count">
            {{ $invitations->total() }} lời mời đang chờ
        </span>
    </div>

    <div class="my-invitations-list">
        @forelse ($invitations as $invitation)
            <article class="panel my-invitation-card">
                <div class="my-invitation-main">
                    <div class="my-invitation-icon" aria-hidden="true">
                        {{ \Illuminate\Support\Str::upper(
                            \Illuminate\Support\Str::substr(
                                $invitation->workspace->name,
                                0,
                                1
                            )
                        ) }}
                    </div>

                    <div class="my-invitation-info">
                        <span class="my-invitation-label">
                            Lời mời tham gia workspace
                        </span>

                        <h2>{{ $invitation->workspace->name }}</h2>

                        <p>
                            Người mời:
                            <strong>
                                {{ $invitation->inviter?->name ?? 'Tài khoản đã xóa' }}
                            </strong>
                        </p>

                        <div class="my-invitation-meta">
                            <span>Vai trò: Thành viên</span>

                            <span>
                                Hết hạn:
                                {{ $invitation->expires_at
                                    ->copy()
                                    ->timezone('Asia/Ho_Chi_Minh')
                                    ->format('d/m/Y H:i') }}
                                (giờ Việt Nam)
                            </span>
                        </div>
                    </div>
                </div>

                <form
                    method="POST"
                    action="{{ route('my-invitations.accept', [
                        'invitation' => $invitation->id,
                    ]) }}"
                    class="my-invitation-action"
                >
                    @csrf

                    <button type="submit" class="primary-button">
                        Chấp nhận lời mời
                    </button>
                </form>
            </article>
        @empty
            <section class="panel my-invitations-empty">
                <div class="my-invitation-icon" aria-hidden="true">
                    W
                </div>

                <h2>Chưa có lời mời đang chờ</h2>

                <p class="muted">
                    Lời mời gửi đến email của bạn sẽ xuất hiện tại đây.
                    Lời mời đã chấp nhận hoặc hết hạn không nằm
                    trong danh sách này.
                </p>

                <a href="{{ route('workspaces.index') }}">
                    Xem workspace của tôi →
                </a>
            </section>
        @endforelse
    </div>

    @if ($invitations->hasPages())
        <nav
            class="workspace-pagination"
            aria-label="Phân trang lời mời"
        >
            @if ($invitations->previousPageUrl())
                <a href="{{ $invitations->previousPageUrl() }}">
                    ← Trang trước
                </a>
            @endif

            <span>
                Trang {{ $invitations->currentPage() }}
                / {{ $invitations->lastPage() }}
            </span>

            @if ($invitations->hasMorePages())
                <a href="{{ $invitations->nextPageUrl() }}">
                    Trang sau →
                </a>
            @endif
        </nav>
    @endif
@endsection
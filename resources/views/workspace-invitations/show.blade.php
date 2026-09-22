@extends('layouts.guest')

@section('title', 'Lời mời tham gia workspace')

@section('content')
    <div class="invitation-preview">
        <span class="invitation-preview__icon" aria-hidden="true">
            W
        </span>

        <h1>Bạn được mời tham gia</h1>

        <h2>{{ $invitation->workspace->name }}</h2>

        <p class="muted">
            Vai trò: Thành viên
        </p>

        <p class="muted">
            Có hiệu lực đến
            {{ $invitation->expires_at
                ->copy()
                ->timezone('Asia/Ho_Chi_Minh')
                ->format('d/m/Y H:i') }}
            (giờ Việt Nam).
        </p>

        @guest
            <p>
                Đăng nhập bằng email nhận được lời mời để tiếp tục.
            </p>

            <a
                href="{{ route('workspace-invitations.continue', ['token' => $token]) }}"
                class="primary-button invitation-action"
            >
                Đăng nhập để tiếp tục
            </a>

            <p>
                Chưa có tài khoản?
                <a href="{{ route('register') }}">Đăng ký</a>
            </p>

            <p class="muted">
                Sau khi đăng ký và xác minh email, hãy mở lại
                liên kết lời mời trong email.
            </p>
        @endguest

        @auth
            @if (
                \Illuminate\Support\Str::lower(trim(auth()->user()->email))
                !== $invitation->email
            )
                <div class="auth-notice">
                    Bạn đang đăng nhập bằng
                    <strong>{{ auth()->user()->email }}</strong>.
                    Tài khoản này không trùng email được mời.
                </div>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <button type="submit" class="primary-button">
                        Đăng xuất để đổi tài khoản
                    </button>
                </form>

                <p class="muted">
                    Sau khi đổi tài khoản, mở lại liên kết lời mời.
                </p>
            @elseif (! auth()->user()->hasVerifiedEmail())
                <div class="auth-notice">
                    Bạn cần xác minh email trước khi tham gia.
                </div>

                <a
                    href="{{ route('workspace-invitations.continue', ['token' => $token]) }}"
                    class="primary-button invitation-action"
                >
                    Xác minh email
                </a>
            @else
                <p>
                    Bạn sẽ tham gia bằng tài khoản
                    <strong>{{ auth()->user()->email }}</strong>.
                </p>

                <form
                    method="POST"
                    action="{{ route('workspace-invitations.accept', ['token' => $token]) }}"
                >
                    @csrf

                    <button type="submit" class="primary-button">
                        Chấp nhận lời mời
                    </button>
                </form>
            @endif
        @endauth
    </div>
@endsection
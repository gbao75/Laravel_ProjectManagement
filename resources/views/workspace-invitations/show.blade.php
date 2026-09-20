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
            Lời mời có hiệu lực đến
            {{ $invitation->expires_at
                ->copy()
                ->timezone('Asia/Ho_Chi_Minh')
                ->format('d/m/Y H:i') }}
            (giờ Việt Nam).
        </p>

        <div class="auth-notice">
            Lời mời hợp lệ. Bạn chưa được thêm vào workspace.
        </div>
    </div>
@endsection
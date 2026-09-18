@extends('layouts.guest')

@section('title', 'Xác minh email')

@section('content')
    <div class="auth-heading">
        <h1>Xác minh email</h1>

        <p class="muted">
            Vui lòng mở email gửi đến
            <strong>{{ auth()->user()->email }}</strong>
            và nhấn liên kết xác minh để tiếp tục.
        </p>
    </div>

    @if (session('status') === 'verification-link-sent')
        <p class="auth-notice" role="status">
            Đã gửi lại email xác minh.
        </p>
    @endif

    @if ($errors->any())
        <div class="field-error" role="alert">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <div class="auth-form">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf

            <button type="submit" class="primary-button full-width">
                Gửi lại email xác minh
            </button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <button type="submit" class="logout-button full-width">
                Đăng xuất
            </button>
        </form>
    </div>
@endsection
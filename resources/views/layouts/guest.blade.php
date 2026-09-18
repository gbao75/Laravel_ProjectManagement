<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Tài khoản') | ProjectManagement</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="auth-body">
    <main class="auth-container">
        <a href="{{ route('dashboard') }}" class="brand auth-brand">
            <span class="brand-icon" aria-hidden="true">PM</span>
            <span>ProjMgmt</span>
        </a>

        <section class="auth-card">
            @yield('content')
        </section>

        <p class="auth-footer">
            Quản lý công việc và cộng tác cùng nhóm.
        </p>
    </main>
</body>
</html>
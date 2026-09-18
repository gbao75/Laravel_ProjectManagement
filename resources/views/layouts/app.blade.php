<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'ProjectManagement') | ProjectManagement</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div class="app-shell">
        <aside class="sidebar" id="sidebar">
            <a href="{{ route('dashboard') }}" class="brand">
                <span class="brand-icon" aria-hidden="true">T</span>
                <span>ProjectManagement</span>
            </a>

            <p class="sidebar-label">KHÔNG GIAN LÀM VIỆC</p>

            <div class="workspace-card">
                <strong>Personal Workspace</strong>
                <span>Quản lý công việc của bạn</span>
            </div>

            <nav class="navigation" aria-label="Điều hướng chính">
                <a
                    href="{{ route('dashboard') }}"
                    class="nav-item is-active"
                    aria-current="page"
                >
                    Tổng quan
                </a>

                <span class="nav-item is-disabled" aria-disabled="true">
                    Công việc của tôi
                    <small>Sắp có</small>
                </span>

                <span class="nav-item is-disabled" aria-disabled="true">
                    Dự án
                    <small>Sắp có</small>
                </span>

                <span class="nav-item is-disabled" aria-disabled="true">
                    Thành viên
                    <small>Sắp có</small>
                </span>
            </nav>

            <p class="sidebar-footer">
                Sắp xếp công việc, theo dõi tiến độ.
            </p>
        </aside>

        <div class="main-column">
            <header class="topbar">
                <div class="topbar-left">
                    <button
                        type="button"
                        class="menu-button"
                        id="menu-toggle"
                        aria-controls="sidebar"
                        aria-expanded="false"
                    >
                        Menu
                    </button>

                    <span class="breadcrumb">
                        Workspace / @yield('page-title', 'Tổng quan')
                    </span>
                </div>

                <div class="account-actions">
        @auth
            <span class="account-placeholder">
                {{ auth()->user()->name }}
            </span>

            <form method="POST" action="{{ route('logout') }}">
                @csrf

                <button type="submit" class="logout-button">
                    Đăng xuất
                </button>
            </form>
        @endauth

        @guest
            <a href="{{ route('login') }}" class="account-placeholder">
                Đăng nhập
            </a>
        @endguest
    </div>
            </header>

            <main class="page-content" id="main-content">
                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>
@extends('layouts.app')

@section('title', 'Tổng quan')
@section('page-title', 'Tổng quan')

@section('content')
    <section class="page-heading">
        <p class="eyebrow">TỔNG QUAN</p>

        <h1>Không gian làm việc của bạn</h1>

        <p class="muted">
            Theo dõi dự án và công việc tại một nơi.
        </p>
    </section>

    <section class="stats-grid" aria-label="Thống kê công việc">
        @foreach ($stats as $stat)
            <article class="stat-card">
                <h2>{{ $stat['label'] }}</h2>

                <p class="stat-value">
                    {{ $stat['value'] }}
                </p>

                <p class="stat-description">
                    {{ $stat['description'] }}
                </p>
            </article>
        @endforeach
    </section>

    <section class="panel">
        <div class="panel-heading">
            <div>
                <h2>Công việc gần đây</h2>
                <p class="muted">
                    Những công việc mới nhất sẽ xuất hiện tại đây.
                </p>
            </div>

            <span class="badge">0 công việc</span>
        </div>

        <div class="empty-state">
            <div class="empty-state-icon" aria-hidden="true">✓</div>

            <h3>Chưa có công việc</h3>

            <p>
                Công việc của bạn sẽ được hiển thị khi được tạo.
            </p>
        </div>
    </section>
@endsection
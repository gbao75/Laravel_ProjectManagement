@extends('layouts.app')

@section('title', 'Thông báo')
@section('page-title', 'Thông báo')

@section('content')
    <div class="space-y-6">
        @if (session('status'))
            <div role="status" class="rounded-xl bg-indigo-50 p-4 text-indigo-800">
                {{ session('status') }}
            </div>
        @endif

        <header class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold">Thông báo</h1>

                <p class="mt-2 text-slate-500">
                    Những cập nhật dành cho bạn.
                </p>
            </div>

            <a
                href="{{ route('notifications.preferences.edit') }}"
                class="text-indigo-600"
            >
                Cài đặt thông báo
            </a>

            <form method="POST" action="{{ route('notifications.read-all') }}">
                @csrf
                @method('PATCH')

                <button
                    type="submit"
                    class="rounded-lg border border-indigo-200 bg-white px-4 py-2 text-indigo-700"
                >
                    Đánh dấu tất cả đã đọc
                </button>
            </form>
        </header>

        <div class="space-y-4">
            @forelse ($notifications as $notification)
                <article
                    class="rounded-2xl border p-5 {{
                        $notification->read_at
                            ? 'border-slate-200 bg-white'
                            : 'border-indigo-200 bg-indigo-50'
                    }}"
                >
                    <div class="flex items-start gap-3">
                        @if (! $notification->read_at)
                            <span
                                class="mt-2 h-2 w-2 shrink-0 rounded-full bg-indigo-600"
                                aria-label="Chưa đọc"
                            ></span>
                        @endif

                        <div class="min-w-0 flex-1">
                            <p class="break-words font-semibold">
                                {{ $notification->data['message'] ?? 'Bạn có thông báo mới.' }}
                            </p>

                            <p class="mt-2 text-sm text-slate-500">
                                {{ $notification->created_at->format('d/m/Y H:i') }}
                                · {{ $notification->read_at ? 'Đã đọc' : 'Chưa đọc' }}
                            </p>

                            <div class="mt-4 flex flex-wrap gap-4">
                                <form
                                    method="POST"
                                    action="{{ route('notifications.open', $notification->id) }}"
                                >
                                    @csrf

                                    <button type="submit" class="font-semibold text-indigo-700">
                                        Xem công việc →
                                    </button>
                                </form>

                                @if (! $notification->read_at)
                                    <form
                                        method="POST"
                                        action="{{ route('notifications.read', $notification->id) }}"
                                    >
                                        @csrf
                                        @method('PATCH')

                                        <button type="submit" class="text-slate-600">
                                            Đánh dấu đã đọc
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                </article>
            @empty
                <div class="rounded-2xl border border-slate-200 bg-white p-8 text-center text-slate-500">
                    Bạn chưa có thông báo.
                </div>
            @endforelse
        </div>

        {{ $notifications->links() }}
    </div>
@endsection
@extends('layouts.app')

@section('title', 'Thời gian làm việc')
@section('page-title', 'Thời gian làm việc')

@section('content')
    <div class="space-y-6">
        @if (session('status'))
            <div role="status" class="rounded-xl bg-emerald-50 p-4 text-emerald-800">
                {{ session('status') }}
            </div>
        @endif

        <header>
            <h1 class="text-3xl font-bold">Thời gian làm việc của tôi</h1>

            <p class="mt-2 text-slate-500">
                Tổng các phiên đã dừng:
                <strong>
                    {{ intdiv($totalSeconds, 3600) }} giờ
                    {{ intdiv($totalSeconds % 3600, 60) }} phút
                </strong>
            </p>
        </header>

        <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white">
            <table class="w-full text-left">
                <thead class="bg-slate-50 text-sm text-slate-500">
                    <tr>
                        <th class="p-4">Công việc</th>
                        <th class="p-4">Bắt đầu</th>
                        <th class="p-4">Thời lượng</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($entries as $entry)
                        <tr class="border-t border-slate-200">
                            <td class="p-4">
                                <strong>{{ $entry->task?->title ?? 'Công việc đã xóa' }}</strong>

                                <p class="mt-1 text-sm text-slate-500">
                                    {{ $entry->task?->project?->name ?? 'Công việc cá nhân' }}
                                </p>
                            </td>

                            <td class="whitespace-nowrap p-4">
                                {{ $entry->started_at
                                    ->setTimezone(config('task_reminders.timezone', 'Asia/Ho_Chi_Minh'))
                                    ->format('d/m/Y H:i') }}
                            </td>

                            <td class="whitespace-nowrap p-4">
                                @if ($entry->ended_at)
                                    @php
                                        $seconds = (int) $entry->duration_seconds;
                                    @endphp

                                    {{ intdiv($seconds, 3600) }} giờ
                                    {{ intdiv($seconds % 3600, 60) }} phút
                                    {{ $seconds % 60 }} giây
                                @else
                                    <span class="font-semibold text-indigo-600">
                                        Đang chạy
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="p-6 text-center text-slate-500">
                                Chưa có phiên làm việc.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $entries->links() }}
    </div>
@endsection     
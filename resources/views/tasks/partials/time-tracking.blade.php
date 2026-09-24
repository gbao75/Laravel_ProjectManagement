@php
    $myTaskSeconds = (int) $task->timeEntries()
        ->where('user_id', auth()->id())
        ->whereNotNull('ended_at')
        ->sum('duration_seconds');

    $myTaskHours = intdiv($myTaskSeconds, 3600);
    $myTaskMinutes = intdiv($myTaskSeconds % 3600, 60);

    $runningEntry = \App\Models\TaskTimeEntry::query()
        ->where('user_id', auth()->id())
        ->whereNull('ended_at')
        ->first();
@endphp

<section class="rounded-2xl border border-slate-200 bg-white p-6">
    <h2 class="text-xl font-bold">Thời gian làm việc của bạn</h2>

    <p class="mt-3 text-slate-600">
        Đã ghi:
        <strong>{{ $myTaskHours }} giờ {{ $myTaskMinutes }} phút</strong>
    </p>

    <p class="mt-1 text-sm text-slate-500">
        Tổng trên chỉ gồm những phiên đã dừng.
    </p>

    @error('timer')
        <p class="mt-3 text-red-600">{{ $message }}</p>
    @enderror

    <div class="mt-4">
        @if ($runningEntry && (int) $runningEntry->task_id === (int) $task->id)
            <p class="mb-3 font-semibold text-indigo-600">
                Đang ghi thời gian cho công việc này.
            </p>

            <form
                method="POST"
                action="{{ route('time-entries.stop', $runningEntry->id) }}"
            >
                @csrf
                @method('PATCH')

                <button
                    type="submit"
                    class="rounded-lg bg-rose-600 px-4 py-2 font-semibold text-white"
                >
                    Dừng và lưu
                </button>
            </form>
        @elseif ($runningEntry)
            <p class="text-amber-700">
                Hãy dừng bộ đếm đang chạy trước khi bắt đầu công việc này.
            </p>
        @else
            @can('trackTime', $task)
                @if ($task->status !== 'completed')
                    <form
                        method="POST"
                        action="{{ route('tasks.timer.start', $task) }}"
                    >
                        @csrf

                        <button
                            type="submit"
                            class="rounded-lg bg-indigo-600 px-4 py-2 font-semibold text-white"
                        >
                            Bắt đầu làm việc
                        </button>
                    </form>
                @else
                    <p class="text-slate-500">Công việc đã hoàn thành.</p>
                @endif
            @endcan
        @endif
    </div>
</section>
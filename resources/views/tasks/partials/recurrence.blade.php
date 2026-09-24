@can('update', $task)
    @php
        $rule = $task->recurrence;

        $today = now(
            config('task_reminders.timezone', 'Asia/Ho_Chi_Minh')
        )->toDateString();

        $defaultDate = $rule?->next_run_on?->toDateString() ?? $today;

        // Khi bật lại lịch cũ, yêu cầu chọn từ hôm nay.
        if ($defaultDate < $today) {
            $defaultDate = $today;
        }

        $inputClass = 'mt-2 w-full rounded-lg border border-slate-300 bg-white px-3 py-2';
    @endphp

    <section class="space-y-4 rounded-2xl border border-slate-200 bg-white p-6">
        <h2 class="text-xl font-bold">Tạo công việc lặp lại</h2>

        <p class="text-sm text-slate-500">
            Dùng công việc này làm mẫu để tự tạo công việc mới.
        </p>

        @if ($rule)
            <div class="rounded-lg bg-slate-50 p-3 text-sm">
                <p>
                    Lịch #{{ $rule->id }}:
                    <strong>
                        {{ $rule->is_active ? 'Đang bật' : 'Đã tạm dừng' }}
                    </strong>
                </p>

                <p class="mt-1">
                    Ngày xử lý tiếp theo:
                    {{ $rule->next_run_on->format('d/m/Y') }}
                </p>

                @if ($rule->paused_reason)
                    <p class="mt-2 text-amber-700">
                        {{ $rule->paused_reason }}
                    </p>
                @endif
            </div>
        @endif

        <form
            method="POST"
            action="{{ route('tasks.recurrence.save', $task) }}"
            class="space-y-4"
        >
            @csrf
            @method('PUT')

            <div>
                <label for="recurrence-frequency" class="font-semibold">
                    Tần suất
                </label>

                <select
                    id="recurrence-frequency"
                    name="frequency"
                    class="{{ $inputClass }}"
                >
                    @foreach (\App\Models\TaskRecurrence::FREQUENCIES as $value => $label)
                        <option
                            value="{{ $value }}"
                            @selected(old('frequency', $rule?->frequency ?? 'weekly') === $value)
                        >
                            {{ $label }}
                        </option>
                    @endforeach
                </select>

                @error('frequency')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="recurrence-date" class="font-semibold">
                    Ngày tạo tiếp theo
                </label>

                <input
                    id="recurrence-date"
                    type="date"
                    name="next_run_on"
                    min="{{ $today }}"
                    value="{{ old('next_run_on', $defaultDate) }}"
                    required
                    class="{{ $inputClass }}"
                >

                @error('next_run_on')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="recurrence-due" class="font-semibold">
                    Hạn sau ngày tạo theo lịch bao nhiêu ngày?
                </label>

                <input
                    id="recurrence-due"
                    type="number"
                    name="due_after_days"
                    min="0"
                    max="365"
                    value="{{ old('due_after_days', $rule?->due_after_days ?? 0) }}"
                    required
                    class="{{ $inputClass }}"
                >

                @error('due_after_days')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <button
                type="submit"
                class="rounded-lg bg-indigo-600 px-4 py-2 font-semibold text-white"
            >
                Lưu và bật lịch
            </button>
        </form>

        @if ($rule?->is_active)
            <form
                method="POST"
                action="{{ route('tasks.recurrence.pause', $task) }}"
            >
                @csrf
                @method('PATCH')

                <button type="submit" class="font-semibold text-rose-600">
                    Tạm dừng lịch lặp
                </button>
            </form>
        @endif
    </section>
@endcan
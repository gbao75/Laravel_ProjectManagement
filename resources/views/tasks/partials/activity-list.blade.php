@php
    $eventLabels = [
        'task.created' => 'đã tạo công việc',
        'task.updated' => 'đã cập nhật công việc',
        'task.deleted' => 'đã xóa công việc',
        'task.mentioned' => 'đã nhắc tên thành viên',
        'attachment.created' => 'đã tải tệp lên',
        'attachment.deleted' => 'đã xóa tệp',
    ];

    $fieldLabels = [
        'title' => 'Tiêu đề',
        'description' => 'Mô tả',
        'status' => 'Trạng thái',
        'priority' => 'Ưu tiên',
        'assigned_to' => 'ID người được giao',
        'due_date' => 'Hạn hoàn thành',
    ];
@endphp

<div class="space-y-4">
    @forelse ($activities as $activity)
        <article class="rounded-xl border border-slate-200 p-4">
            <p>
                <strong>
                    {{ $activity->actor?->name ?? 'Hệ thống / tài khoản đã xóa' }}
                </strong>

                {{ $eventLabels[$activity->event] ?? $activity->event }}:

                <strong>{{ $activity->task_title }}</strong>
            </p>

            <p class="mt-1 text-sm text-slate-500">
                {{ $activity->created_at->format('d/m/Y H:i') }}
            </p>

            @if ($activity->event === 'task.updated')
                <ul class="mt-3 space-y-2 text-sm">
                    @foreach (($activity->changes ?? []) as $field => $change)
                        @php
                            $oldValue = $change['old'] ?? null;
                            $newValue = $change['new'] ?? null;

                            if ($field === 'status') {
                                $oldValue = \App\Models\Task::STATUSES[$oldValue] ?? $oldValue;
                                $newValue = \App\Models\Task::STATUSES[$newValue] ?? $newValue;
                            }

                            if ($field === 'priority') {
                                $oldValue = \App\Models\Task::PRIORITIES[$oldValue] ?? $oldValue;
                                $newValue = \App\Models\Task::PRIORITIES[$newValue] ?? $newValue;
                            }
                        @endphp

                        <li class="break-words">
                            <strong>{{ $fieldLabels[$field] ?? $field }}:</strong>

                            {{ \Illuminate\Support\Str::limit((string) ($oldValue ?? 'Trống'), 150) }}

                            →

                            {{ \Illuminate\Support\Str::limit((string) ($newValue ?? 'Trống'), 150) }}
                        </li>
                    @endforeach
                </ul>
            @endif

            @if ($activity->event === 'task.mentioned')
                <p class="mt-2 text-sm text-indigo-700">
                    Người được nhắc:
                    {{ implode(', ', $activity->changes['recipient_names'] ?? []) }}
                </p>
            @endif

            @if (isset($activity->changes['name']))
                <p class="mt-2 break-words text-sm">
                    Tệp: {{ $activity->changes['name'] }}
                </p>
            @endif
        </article>
    @empty
        <p class="text-slate-500">Chưa có hoạt động.</p>
    @endforelse
</div>

<div class="mt-5">
    {{ $activities->links() }}
</div>
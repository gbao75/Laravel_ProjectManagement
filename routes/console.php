<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Models\TaskAttachment;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('attachments:prune', function () {
    $disk = Storage::disk('task_attachments');

    $cutoff = now()->subDay()->timestamp;
    $deleted = 0;

    foreach ($disk->allFiles('tasks') as $path) {
        // Chừa file mới để tránh ảnh hưởng upload đang diễn ra.
        if ($disk->lastModified($path) >= $cutoff) {
            continue;
        }

        $isReferenced = TaskAttachment::query()
            ->where('path', $path)
            ->exists();

        if ($isReferenced) {
            continue;
        }

        if ($disk->delete($path)) {
            $deleted++;
        }
    }

    $this->info("Đã dọn {$deleted} tệp không còn được sử dụng.");
})->purpose('Dọn tệp đính kèm mồ côi cũ hơn một ngày');

Schedule::command('tasks:send-deadline-reminders')
    ->everyFiveMinutes()
    ->withoutOverlapping(30);

Schedule::command('attachments:prune')
    ->dailyAt('02:00')
    ->timezone(config('task_reminders.timezone'))
    ->withoutOverlapping(60);

Schedule::command('tasks:generate-recurring')
    ->everyFiveMinutes()
    ->withoutOverlapping(30);
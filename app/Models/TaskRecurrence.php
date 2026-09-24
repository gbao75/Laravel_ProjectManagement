<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskRecurrence extends Model
{
    public const FREQUENCIES = [
        'daily' => 'Hằng ngày',
        'weekly' => 'Hằng tuần',
        'monthly' => 'Hằng tháng',
    ];

    protected function casts(): array
    {
        return [
            'next_run_on' => 'immutable_date',
            'is_active' => 'boolean',
            'anchor_day' => 'integer',
            'due_after_days' => 'integer',
        ];
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function nextDate(): CarbonImmutable
    {
        $date = $this->next_run_on;

        return match ($this->frequency) {
            'daily' => $date->addDay(),
            'weekly' => $date->addWeek(),
            'monthly' => $this->nextMonthlyDate($date),
        };
    }

    private function nextMonthlyDate(
        CarbonImmutable $date
    ): CarbonImmutable {
        $nextMonth = $date->startOfMonth()->addMonth();

        return $nextMonth->day(
            min($this->anchor_day, $nextMonth->daysInMonth)
        );
    }
}
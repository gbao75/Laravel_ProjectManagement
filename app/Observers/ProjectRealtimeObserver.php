<?php

namespace App\Observers;

use App\Events\ProjectBoardChanged;
use App\Models\Project;

class ProjectRealtimeObserver
{
    public function updated(Project $project): void
    {
        if (! $project->wasChanged('board_version')) {
            return;
        }

        ProjectBoardChanged::dispatch(
            (int) $project->id,
            (int) $project->board_version
        );
    }
}
<?php

namespace App\Services;

use App\Models\Project;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ProjectBoardService
{
    public function write(
        Project $project,
        Closure $callback,
        ?int $expectedVersion = null
    ): array {
        return DB::transaction(function () use (
            $project,
            $callback,
            $expectedVersion
        ) {
            $lockedProject = Project::query()
                ->whereKey($project->id)
                ->lockForUpdate()
                ->firstOrFail();

            Gate::authorize('view', $lockedProject);

            if (
                $expectedVersion !== null
                && (int) $lockedProject->board_version !== $expectedVersion
            ) {
                abort(
                    409,
                    'Bảng đã thay đổi ở nơi khác. Vui lòng tải lại trước khi thao tác.'
                );
            }

            $result = $callback($lockedProject);

            $lockedProject->increment('board_version');

            return [
                'result' => $result,
                'version' => (int) $lockedProject->board_version,
            ];
        }, 3);
    }
}
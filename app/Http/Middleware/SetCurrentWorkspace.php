<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class SetCurrentWorkspace
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Middleware này cần được đặt sau middleware auth.
        if (! $user) {
            return $next($request);
        }

        $workspaces = $user->workspaces()
            ->orderBy('workspaces.name')
            ->orderBy('workspaces.id')
            ->get();

        $selectedId = (int) $request->session()
            ->get('current_workspace_id');

        $currentWorkspace = $workspaces->first(
            fn ($workspace) => (int) $workspace->id === $selectedId
        );

        // Workspace đã xóa hoặc người dùng không còn là thành viên:
        // chọn workspace đầu tiên còn được phép truy cập.
        $currentWorkspace ??= $workspaces->first();

        if ($currentWorkspace) {
            $request->session()->put(
                'current_workspace_id',
                $currentWorkspace->id
            );
        } else {
            $request->session()->forget('current_workspace_id');
        }

        View::share('switcherWorkspaces', $workspaces);
        View::share('currentWorkspace', $currentWorkspace);

        return $next($request);
    }
}
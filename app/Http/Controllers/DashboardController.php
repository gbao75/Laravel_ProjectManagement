<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\MyWorkQuery;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(
        Request $request,
        MyWorkQuery $work
    ): View {
        $user = $request->user();

        $today = now(
            config('task_reminders.timezone', 'Asia/Ho_Chi_Minh')
        )->toDateString();

        $query = $work->tasks($user);

        $totalTasks = (clone $query)->count();

        $completedTasks = (clone $query)
            ->where('status', 'completed')
            ->count();

        $inProgressTasks = (clone $query)
            ->where('status', 'in_progress')
            ->count();

        $overdueTasks = (clone $query)
            ->where('status', '!=', 'completed')
            ->whereNotNull('due_date')
            ->where('due_date', '<', $today)
            ->count();

        $projectCount = Project::query()
            ->accessibleTo($user)
            ->count();

        $completionPercent = $totalTasks > 0
            ? (int) round($completedTasks * 100 / $totalTasks)
            : 0;

        $upcomingTasks = (clone $query)
            ->with('project.workspace')
            ->where('status', '!=', 'completed')
            ->whereNotNull('due_date')
            ->where('due_date', '>=', $today)
            ->orderBy('due_date')
            ->orderBy('id')
            ->limit(6)
            ->get();

        $recentTasks = (clone $query)
            ->with('project.workspace')
            ->latest('updated_at')
            ->orderByDesc('id')
            ->limit(8)
            ->get();

        return view('dashboard', compact(
            'totalTasks',
            'completedTasks',
            'inProgressTasks',
            'overdueTasks',
            'projectCount',
            'completionPercent',
            'upcomingTasks',
            'recentTasks'
        ));
    }
}
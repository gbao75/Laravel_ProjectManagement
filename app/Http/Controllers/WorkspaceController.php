<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWorkspaceRequest;
use App\Models\Workspace;
use App\Services\WorkspaceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use App\Http\Requests\UpdateWorkspaceRequest;

class WorkspaceController extends Controller
{
    public function index(Request $request): View
    {
        $workspaces = $request->user()
            ->workspaces()
            ->with('owner')
            ->withCount('members')
            ->orderByDesc('workspaces.created_at')
            ->orderByDesc('workspaces.id')
            ->paginate(9);

        return view('workspaces.index', compact('workspaces'));
    }

    public function create(): View
    {
        Gate::authorize('create', Workspace::class);

        return view('workspaces.create');
    }

    public function store(
        StoreWorkspaceRequest $request,
        WorkspaceService $workspaceService
    ): RedirectResponse {
        Gate::authorize('create', Workspace::class);

        $workspace = $workspaceService->create(
            $request->user(),
            $request->validated()
        );

        return redirect()
            ->route('workspaces.show', $workspace)
            ->with('status', 'Tạo workspace thành công.');
    }

    public function show(Workspace $workspace): View
    {
        Gate::authorize('view', $workspace);

        $workspace->load('owner');
        $workspace->loadCount('members');

        return view('workspaces.show', compact('workspace'));
    }

    public function switchWorkspace(Request $request): RedirectResponse
{
    $data = $request->validate([
        'workspace_id' => ['required', 'integer'],
    ], [
        'workspace_id.required' => 'Vui lòng chọn workspace.',
        'workspace_id.integer' => 'Workspace không hợp lệ.',
    ]);

    $workspace = Workspace::query()
        ->findOrFail($data['workspace_id']);

    Gate::authorize('view', $workspace);

    $request->session()->put(
        'current_workspace_id',
        $workspace->id
    );

    return redirect()
        ->route('workspaces.show', $workspace)
        ->with('status', 'Đã chuyển workspace.');
}

    public function edit(Workspace $workspace): View
    {
        Gate::authorize('update', $workspace);

        return view('workspaces.edit', compact('workspace'));
    }

    public function update(
        UpdateWorkspaceRequest $request,
        Workspace $workspace
    ): RedirectResponse {
        // UpdateWorkspaceRequest đã kiểm tra quyền update.
        $workspace->update($request->validated());

        return redirect()
            ->route('workspaces.show', $workspace)
            ->with('status', 'Đã cập nhật workspace.');
    }

    public function destroy(
        Request $request,
        Workspace $workspace
    ): RedirectResponse {
        Gate::authorize('delete', $workspace);

        $workspaceId = $workspace->id;

        $workspace->delete();

        if (
            (int) $request->session()->get('current_workspace_id')
            === (int) $workspaceId
        ) {
            $request->session()->forget('current_workspace_id');
        }

        return redirect()
            ->route('workspaces.index')
            ->with('status', 'Đã xóa workspace.');
    }
}
<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;
use App\Services\WorkspaceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private Workspace $workspace;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        $this->owner = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $this->workspace = app(WorkspaceService::class)->create(
            $this->owner,
            ['name' => 'Workspace A']
        );
    }

    private function project(): Project
    {
        return $this->workspace->projects()->create([
            'name' => 'Dự án A',
            'status' => 'planning',
        ]);
    }

    private function url(string $action, Project $project): string
    {
        return route("workspaces.projects.{$action}", [
            'workspace' => $this->workspace,
            'project' => $project,
        ]);
    }

    public function test_owner_creates_project_with_server_assigned_ids(): void
    {
        $otherUser = User::factory()->create();

        $otherWorkspace = app(WorkspaceService::class)->create(
            $otherUser,
            ['name' => 'Workspace B']
        );

        $this->actingAs($this->owner)
            ->post(
                route('workspaces.projects.store', $this->workspace),
                [
                    'name' => 'Dự án mới',
                    'status' => 'planning',

                    // Thử gửi giả các trường không được phép.
                    'workspace_id' => $otherWorkspace->id,
                    'created_by' => $otherUser->id,
                ]
            )
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas('projects', [
            'name' => 'Dự án mới',
            'workspace_id' => $this->workspace->id,
            'created_by' => $this->owner->id,
        ]);
    }

    public function test_admin_can_create_update_but_not_delete(): void
    {
        $admin = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $this->workspace->members()->attach($admin->id, [
            'role' => 'admin',
            'joined_at' => now(),
        ]);

        $this->actingAs($admin)
            ->post(
                route('workspaces.projects.store', $this->workspace),
                [
                    'name' => 'Dự án của Admin',
                    'status' => 'planning',
                ]
            )
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $project = Project::query()->sole();

        $this->put($this->url('update', $project), [
            'name' => 'Dự án đã sửa',
            'status' => 'in_progress',
        ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'status' => 'in_progress',
        ]);

        $this->delete($this->url('destroy', $project))
            ->assertForbidden();

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
        ]);
    }

    public function test_member_can_view_but_cannot_write(): void
    {
        $member = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $this->workspace->members()->attach($member->id, [
            'role' => 'member',
            'joined_at' => now(),
        ]);

        $project = $this->project();

        $this->actingAs($member)
            ->get($this->url('show', $project))
            ->assertOk();

        $this->post(
            route('workspaces.projects.store', $this->workspace),
            ['name' => 'Không được tạo', 'status' => 'planning']
        )->assertForbidden();

        $this->put($this->url('update', $project), [
            'name' => 'Không được sửa',
            'status' => 'completed',
        ])->assertForbidden();

        $this->delete($this->url('destroy', $project))
            ->assertForbidden();

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'name' => 'Dự án A',
            'status' => 'planning',
        ]);
    }

    public function test_outsider_cannot_access_projects(): void
    {
        $outsider = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $project = $this->project();

        $this->actingAs($outsider)
            ->get(route('workspaces.projects.index', $this->workspace))
            ->assertForbidden();

        $this->get($this->url('show', $project))
            ->assertForbidden();
    }

    public function test_projects_cannot_be_mixed_between_workspaces(): void
    {
        // Owner tham gia cả hai workspace:
        // vẫn không được ghép URL workspace A với dự án B.
        $otherWorkspace = app(WorkspaceService::class)->create(
            $this->owner,
            ['name' => 'Workspace B']
        );

        $foreignProject = $otherWorkspace->projects()->create([
            'name' => 'Dự án riêng của B',
            'status' => 'planning',
        ]);

        $this->actingAs($this->owner)
            ->get($this->url('show', $foreignProject))
            ->assertNotFound();

        $this->put($this->url('update', $foreignProject), [
            'name' => 'Tên sửa trái workspace',
            'status' => 'completed',
        ])->assertNotFound();

        $this->delete($this->url('destroy', $foreignProject))
            ->assertNotFound();

        $this->get(
            route('workspaces.projects.index', $this->workspace)
        )
            ->assertOk()
            ->assertDontSee('Dự án riêng của B');

        $this->assertDatabaseHas('projects', [
            'id' => $foreignProject->id,
            'name' => 'Dự án riêng của B',
        ]);
    }

    public function test_due_date_cannot_precede_start_date(): void
    {
        $this->actingAs($this->owner)
            ->post(
                route('workspaces.projects.store', $this->workspace),
                [
                    'name' => 'Dự án sai ngày',
                    'status' => 'planning',
                    'start_date' => '2026-10-20',
                    'due_date' => '2026-10-10',
                ]
            )
            ->assertSessionHasErrors('due_date');

        $this->assertDatabaseCount('projects', 0);
    }

    public function test_owner_can_delete_project(): void
    {
        $project = $this->project();

        $this->actingAs($this->owner)
            ->delete($this->url('destroy', $project))
            ->assertRedirect(
                route('workspaces.projects.index', $this->workspace)
            );

        $this->assertDatabaseMissing('projects', [
            'id' => $project->id,
        ]);

        $this->assertDatabaseHas('workspaces', [
            'id' => $this->workspace->id,
        ]);
    }
}
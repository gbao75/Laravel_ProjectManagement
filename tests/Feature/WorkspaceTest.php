<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Workspace;
use App\Services\WorkspaceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkspaceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_creator_becomes_owner_and_member(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $otherUser = User::factory()->create();

        $response = $this->actingAs($user)->post(
            route('workspaces.store'),
            [
                'name' => 'Đội phát triển website',
                'description' => 'Workspace thử nghiệm',

                // Thử giả mạo các trường không được phép nhập.
                'owner_id' => $otherUser->id,
                'role' => 'admin',
                'is_personal' => true,
            ]
        );

        $response->assertSessionHasNoErrors();

        $workspace = Workspace::query()->sole();

        $response->assertRedirect(
            route('workspaces.show', $workspace)
        );

        $this->assertSame($user->id, $workspace->owner_id);
        $this->assertFalse($workspace->is_personal);

        $this->assertDatabaseHas('workspace_user', [
            'workspace_id' => $workspace->id,
            'user_id' => $user->id,
            'role' => 'member',
        ]);

        $this->assertDatabaseMissing('workspace_user', [
            'workspace_id' => $workspace->id,
            'user_id' => $otherUser->id,
        ]);
    }

    public function test_outsider_cannot_view_workspace(): void
    {
        $owner = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $outsider = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $workspace = app(WorkspaceService::class)->create($owner, [
            'name' => 'Workspace riêng',
        ]);

        $this->actingAs($outsider)
            ->get(route('workspaces.show', $workspace))
            ->assertForbidden();
    }

    public function test_member_can_view_workspace(): void
    {
        $owner = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $member = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $workspace = app(WorkspaceService::class)->create($owner, [
            'name' => 'Workspace chung',
        ]);

        $workspace->members()->attach($member->id, [
            'role' => 'member',
            'joined_at' => now(),
        ]);

        $this->actingAs($member)
            ->get(route('workspaces.show', $workspace))
            ->assertOk()
            ->assertSee('Workspace chung');
    }

    public function test_list_only_contains_joined_workspaces(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $otherUser = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        app(WorkspaceService::class)->create($user, [
            'name' => 'Workspace được phép xem',
        ]);

        app(WorkspaceService::class)->create($otherUser, [
            'name' => 'Workspace không được phép xem',
        ]);

        $this->actingAs($user)
            ->get(route('workspaces.index'))
            ->assertOk()
            ->assertSee('Workspace được phép xem')
            ->assertDontSee('Workspace không được phép xem');
    }

    public function test_name_is_required(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)
            ->post(route('workspaces.store'), [
                'name' => '',
            ])
            ->assertSessionHasErrors('name');

        $this->assertDatabaseCount('workspaces', 0);
        $this->assertDatabaseCount('workspace_user', 0);
    }

    public function test_owner_can_delete_workspace_and_memberships(): void
    {
        $owner = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $workspace = app(WorkspaceService::class)->create($owner, [
            'name' => 'Workspace thử xóa',
        ]);

        $this->actingAs($owner)
            ->withSession([
                'current_workspace_id' => $workspace->id,
            ])
            ->delete(route('workspaces.destroy', $workspace))
            ->assertRedirect(route('workspaces.index'))
            ->assertSessionMissing('current_workspace_id');

        $this->assertDatabaseMissing('workspaces', [
            'id' => $workspace->id,
        ]);

        $this->assertDatabaseMissing('workspace_user', [
            'workspace_id' => $workspace->id,
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $owner->id,
        ]);
    }

    public function test_admin_can_update_but_cannot_delete_workspace(): void
    {
        $owner = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $admin = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $workspace = app(WorkspaceService::class)->create($owner, [
            'name' => 'Tên ban đầu',
        ]);

        $workspace->members()->attach($admin->id, [
            'role' => 'admin',
            'joined_at' => now(),
        ]);

        $this->actingAs($admin)
            ->put(route('workspaces.update', $workspace), [
                'name' => 'Tên đã cập nhật',
                'description' => 'Admin chỉnh sửa',
            ])
            ->assertRedirect(route('workspaces.show', $workspace));

        $this->assertDatabaseHas('workspaces', [
            'id' => $workspace->id,
            'name' => 'Tên đã cập nhật',
            'slug' => $workspace->slug,
            'owner_id' => $owner->id,
        ]);

        $this->delete(route('workspaces.destroy', $workspace))
            ->assertForbidden();

        $this->assertDatabaseHas('workspaces', [
            'id' => $workspace->id,
        ]);
    }

    public function test_member_cannot_update_or_delete_workspace(): void
    {
        $owner = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $member = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $workspace = app(WorkspaceService::class)->create($owner, [
            'name' => 'Tên được bảo vệ',
        ]);

        $workspace->members()->attach($member->id, [
            'role' => 'member',
            'joined_at' => now(),
        ]);

        $this->actingAs($member)
            ->put(route('workspaces.update', $workspace), [
                'name' => 'Tên không được phép đổi',
            ])
            ->assertForbidden();

        $this->delete(route('workspaces.destroy', $workspace))
            ->assertForbidden();

        $this->assertDatabaseHas('workspaces', [
            'id' => $workspace->id,
            'name' => 'Tên được bảo vệ',
        ]);
    }

    public function test_user_can_switch_only_to_joined_workspaces(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $otherUser = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $ownWorkspace = app(WorkspaceService::class)->create($user, [
            'name' => 'Workspace của tôi',
        ]);

        $otherWorkspace = app(WorkspaceService::class)->create($otherUser, [
            'name' => 'Workspace người khác',
        ]);

        $this->actingAs($user)
            ->post(route('workspaces.switch'), [
                'workspace_id' => $ownWorkspace->id,
            ])
            ->assertRedirect(route('workspaces.show', $ownWorkspace))
            ->assertSessionHas(
                'current_workspace_id',
                $ownWorkspace->id
            );

        $this->post(route('workspaces.switch'), [
            'workspace_id' => $otherWorkspace->id,
        ])
            ->assertForbidden()
            ->assertSessionHas(
                'current_workspace_id',
                $ownWorkspace->id
            );
    }
}
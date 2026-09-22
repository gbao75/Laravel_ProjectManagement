<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceInvitation;
use App\Services\WorkspaceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkspaceMembershipTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private User $invitee;
    private Workspace $workspace;
    private WorkspaceInvitation $invitation;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        $this->owner = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $this->invitee = User::factory()->create([
            'email' => 'invitee@example.com',
            'email_verified_at' => now(),
        ]);

        $this->workspace = app(WorkspaceService::class)->create(
            $this->owner,
            ['name' => 'Workspace thử nghiệm']
        );

        $this->token = str_repeat('a', 64);

        $this->invitation = WorkspaceInvitation::create([
            'workspace_id' => $this->workspace->id,
            'invited_by' => $this->owner->id,
            'email' => $this->invitee->email,
            'role' => 'member',
            'token_hash' => hash('sha256', $this->token),
            'expires_at' => now()->addDays(7),
        ]);
    }

    private function acceptUrl(): string
    {
        return route('workspace-invitations.accept', [
            'token' => $this->token,
        ]);
    }

    private function memberUrl(string $action, User $user): string
    {
        return route("workspaces.members.{$action}", [
            'workspace' => $this->workspace,
            'member' => $user->id,
        ]);
    }

    public function test_recipient_can_accept_only_once(): void
    {
        $this->actingAs($this->invitee)
            ->post($this->acceptUrl())
            ->assertRedirect(
                route('workspaces.show', $this->workspace)
            );

        $this->assertDatabaseHas('workspace_user', [
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->invitee->id,
            'role' => 'member',
        ]);

        $this->assertNotNull(
            $this->invitation->fresh()->accepted_at
        );

        $this->post($this->acceptUrl())->assertStatus(410);

        $this->assertSame(
            2,
            $this->workspace->members()->count()
        );
    }

    public function test_wrong_email_cannot_accept(): void
    {
        $wrongUser = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $this->actingAs($wrongUser)
            ->post($this->acceptUrl())
            ->assertForbidden();

        $this->assertNull(
            $this->invitation->fresh()->accepted_at
        );

        $this->assertSame(
            1,
            $this->workspace->members()->count()
        );
    }

    public function test_unverified_user_cannot_accept(): void
    {
        // Gán trực tiếp để thiết lập trạng thái tài khoản trong test.
        $this->invitee->email_verified_at = null;
        $this->invitee->save();

        $user = $this->invitee->fresh();

        // Kiểm tra dữ liệu chuẩn bị đã đúng trước khi gửi yêu cầu.
        $this->assertNull($user->email_verified_at);
        $this->assertFalse($user->hasVerifiedEmail());

        $this->actingAs($user)
            ->post($this->acceptUrl())
            ->assertRedirect(route('verification.notice'));

        $this->assertNull(
            $this->invitation->fresh()->accepted_at
        );

        $this->assertSame(
            1,
            $this->workspace->members()->count()
        );
    }

    public function test_expired_invitation_cannot_be_accepted(): void
    {
        $this->invitation->update([
            'expires_at' => now()->subMinute(),
        ]);

        $this->actingAs($this->invitee)
            ->post($this->acceptUrl())
            ->assertStatus(410);

        $this->assertSame(
            1,
            $this->workspace->members()->count()
        );
    }

    public function test_owner_can_change_role_and_remove_member(): void
    {
        $this->actingAs($this->invitee)
            ->post($this->acceptUrl())
            ->assertRedirect();

        $this->actingAs($this->owner)
            ->put($this->memberUrl('update', $this->invitee), [
                'role' => 'admin',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas('workspace_user', [
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->invitee->id,
            'role' => 'admin',
        ]);

        $this->delete($this->memberUrl('destroy', $this->invitee))
            ->assertRedirect();

        $this->assertDatabaseMissing('workspace_user', [
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->invitee->id,
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $this->invitee->id,
        ]);

        // Thành viên bị xóa không được dùng lại lời mời cũ.
        $this->actingAs($this->invitee)
            ->post($this->acceptUrl())
            ->assertStatus(410);
    }

    public function test_owner_cannot_change_or_remove_self(): void
    {
        $this->actingAs($this->owner)
            ->put($this->memberUrl('update', $this->owner), [
                'role' => 'admin',
            ])
            ->assertForbidden();

        $this->delete($this->memberUrl('destroy', $this->owner))
            ->assertForbidden();

        $this->assertDatabaseHas('workspace_user', [
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->owner->id,
        ]);
    }

    public function test_admin_cannot_manage_other_members(): void
    {
        $admin = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $this->workspace->members()->attach($admin->id, [
            'role' => 'admin',
            'joined_at' => now(),
        ]);

        $this->workspace->members()->attach($this->invitee->id, [
            'role' => 'member',
            'joined_at' => now(),
        ]);

        $this->actingAs($admin)
            ->put($this->memberUrl('update', $this->invitee), [
                'role' => 'admin',
            ])
            ->assertForbidden();

        $this->delete($this->memberUrl('destroy', $this->invitee))
            ->assertForbidden();

        $this->assertDatabaseHas('workspace_user', [
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->invitee->id,
            'role' => 'member',
        ]);
    }

    public function test_member_actions_are_scoped_to_workspace(): void
    {
        // invitee chưa chấp nhận nên đang là người ngoài workspace.
        $this->actingAs($this->invitee)
            ->get(route('workspaces.members.index', $this->workspace))
            ->assertForbidden();

        $this->actingAs($this->owner)
            ->delete($this->memberUrl('destroy', $this->invitee))
            ->assertNotFound();

        $this->assertDatabaseHas('users', [
            'id' => $this->invitee->id,
        ]);
    }
}
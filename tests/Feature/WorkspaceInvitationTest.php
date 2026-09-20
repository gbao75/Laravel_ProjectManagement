<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WorkspaceInvitation;
use App\Notifications\WorkspaceInvitationNotification;
use App\Services\WorkspaceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class WorkspaceInvitationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        Notification::fake();
    }

    public function test_owner_can_invite_by_email(): void
    {
        $owner = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $workspace = app(WorkspaceService::class)->create($owner, [
            'name' => 'Workspace thử nghiệm',
        ]);

        $this->actingAs($owner)
            ->post(route('workspaces.invitations.store', $workspace), [
                'email' => '  NewMember@Example.com  ',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('workspaces.show', $workspace));

        $this->assertDatabaseHas('workspace_invitations', [
            'workspace_id' => $workspace->id,
            'invited_by' => $owner->id,
            'email' => 'newmember@example.com',
            'role' => 'member',
            'accepted_at' => null,
        ]);

        Notification::assertSentOnDemand(
            WorkspaceInvitationNotification::class,
            function ($notification, $channels, $notifiable) {
                return $notifiable->routes['mail']
                    === 'newmember@example.com';
            }
        );

        // Gửi lời mời chưa làm tăng số thành viên.
        $this->assertSame(1, $workspace->members()->count());
    }

    public function test_member_cannot_invite(): void
    {
        $owner = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $member = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $workspace = app(WorkspaceService::class)->create($owner, [
            'name' => 'Workspace phân quyền',
        ]);

        $workspace->members()->attach($member->id, [
            'role' => 'member',
            'joined_at' => now(),
        ]);

        $this->actingAs($member)
            ->post(route('workspaces.invitations.store', $workspace), [
                'email' => 'new@example.com',
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('workspace_invitations', 0);

        Notification::assertNothingSent();
    }

    public function test_pending_invitation_cannot_be_duplicated(): void
    {
        $owner = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $workspace = app(WorkspaceService::class)->create($owner, [
            'name' => 'Workspace kiểm tra trùng',
        ]);

        $url = route('workspaces.invitations.store', $workspace);

        $this->actingAs($owner)
            ->post($url, ['email' => 'new@example.com'])
            ->assertSessionHasNoErrors();

        $this->post($url, ['email' => 'NEW@example.com'])
            ->assertSessionHasErrors('email');

        $this->assertDatabaseCount('workspace_invitations', 1);

        Notification::assertCount(1);
    }

    public function test_invitation_link_expires(): void
    {
        $owner = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $workspace = app(WorkspaceService::class)->create($owner, [
            'name' => 'Workspace kiểm tra thời hạn',
        ]);

        $token = str_repeat('a', 64);

        $invitation = WorkspaceInvitation::create([
            'workspace_id' => $workspace->id,
            'invited_by' => $owner->id,
            'email' => 'new@example.com',
            'role' => 'member',
            'token_hash' => hash('sha256', $token),
            'expires_at' => now()->addDay(),
        ]);

        $url = route('workspace-invitations.show', [
            'token' => $token,
        ]);

        // Người chưa đăng nhập vẫn xem được lời mời hợp lệ.
        $this->get($url)
            ->assertOk()
            ->assertSee('Workspace kiểm tra thời hạn');

        $invitation->update([
            'expires_at' => now()->subMinute(),
        ]);

        $this->get($url)->assertStatus(410);
    }
}
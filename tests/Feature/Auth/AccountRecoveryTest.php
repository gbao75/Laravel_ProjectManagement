<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class AccountRecoveryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        Notification::fake();
    }

    public function test_unverified_user_cannot_access_dashboard(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertRedirect(route('verification.notice'));
    }

    public function test_verified_user_can_access_dashboard(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk();
    }

    public function test_valid_link_verifies_email(): void
    {
        $user = User::factory()->unverified()->create();

        $url = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => $user->id,
                'hash' => sha1($user->getEmailForVerification()),
            ]
        );

        $this->actingAs($user)->get($url)->assertRedirect();

        $this->assertTrue($user->fresh()->hasVerifiedEmail());

        $this->get('/dashboard')->assertOk();
    }

    public function test_expired_verification_link_is_rejected(): void
    {
        $user = User::factory()->unverified()->create();

        $url = URL::temporarySignedRoute(
            'verification.verify',
            now()->subMinute(),
            [
                'id' => $user->id,
                'hash' => sha1($user->getEmailForVerification()),
            ]
        );

        $this->actingAs($user)->get($url)->assertForbidden();

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_tampered_verification_link_is_rejected(): void
    {
        $user = User::factory()->unverified()->create();

        $url = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => $user->id,
                'hash' => sha1($user->getEmailForVerification()),
            ]
        );

        $this->actingAs($user)
            ->get($url . '&tampered=1')
            ->assertForbidden();

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_password_reset_email_is_sent(): void
    {
        $user = User::factory()->create();

        $this->post('/forgot-password', [
            'email' => $user->email,
        ])->assertSessionHasNoErrors();

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_password_can_be_reset_and_token_cannot_be_reused(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('OldPassword2026'),
        ]);

        $broker = Password::broker(config('fortify.passwords'));
        $token = $broker->createToken($user);

        $data = [
            'email' => $user->email,
            'token' => $token,
            'password' => 'NewPassword2026',
            'password_confirmation' => 'NewPassword2026',
        ];

        $this->post('/reset-password', $data)
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('login'));

        $this->assertTrue(
            Hash::check('NewPassword2026', $user->fresh()->password)
        );

        $this->assertFalse(
            Hash::check('OldPassword2026', $user->fresh()->password)
        );

        $this->post('/reset-password', $data)
            ->assertSessionHasErrors('email');
    }

    public function test_invalid_reset_token_cannot_change_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('OldPassword2026'),
        ]);

        $this->post('/reset-password', [
            'email' => $user->email,
            'token' => 'invalid-token',
            'password' => 'NewPassword2026',
            'password_confirmation' => 'NewPassword2026',
        ])->assertSessionHasErrors('email');

        $this->assertTrue(
            Hash::check('OldPassword2026', $user->fresh()->password)
        );
    }
}
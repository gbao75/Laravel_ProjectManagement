<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        Notification::fake();
    }

    public function test_guest_cannot_access_profile(): void
    {
        $this->get('/profile')->assertRedirect('/login');
    }

    public function test_unverified_user_can_access_profile(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->get('/profile')->assertOk();
    }

    public function test_name_change_keeps_email_verified(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)->put('/profile', [
            'name' => 'Bao Updated',
            'email' => $user->email,
        ])->assertSessionHasNoErrors();

        $this->assertSame('Bao Updated', $user->fresh()->name);
        $this->assertTrue($user->fresh()->hasVerifiedEmail());

        Notification::assertNothingSent();
    }

    public function test_email_change_requires_current_password(): void
    {
        $user = User::factory()->create();
        $oldEmail = $user->email;

        $this->actingAs($user)->put('/profile', [
            'name' => $user->name,
            'email' => 'new@example.com',
        ])->assertSessionHasErrors(
            ['current_password'],
            null,
            'profile'
        );

        $this->assertSame($oldEmail, $user->fresh()->email);
    }

    public function test_email_change_requires_new_verification(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('BaoTest2026'),
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)->put('/profile', [
            'name' => $user->name,
            'email' => 'new@example.com',
            'current_password' => 'BaoTest2026',
        ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('verification.notice'));

        $this->assertSame('new@example.com', $user->fresh()->email);
        $this->assertNull($user->fresh()->email_verified_at);

        Notification::assertSentTo($user, VerifyEmail::class);

        $this->get('/dashboard')
            ->assertRedirect(route('verification.notice'));
    }

    public function test_wrong_current_password_prevents_password_change(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('BaoTest2026'),
        ]);

        $this->actingAs($user)->put('/profile/password', [
            'current_password' => 'WrongPassword2026',
            'password' => 'NewPassword2026',
            'password_confirmation' => 'NewPassword2026',
        ])->assertSessionHasErrors(
            ['current_password'],
            null,
            'password'
        );

        $this->assertTrue(
            Hash::check('BaoTest2026', $user->fresh()->password)
        );
    }

    public function test_user_can_change_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('BaoTest2026'),
        ]);

        $this->actingAs($user)->put('/profile/password', [
            'current_password' => 'BaoTest2026',
            'password' => 'NewPassword2026',
            'password_confirmation' => 'NewPassword2026',
        ])->assertSessionHasNoErrors();

        $this->assertTrue(
            Hash::check('NewPassword2026', $user->fresh()->password)
        );

        $this->assertFalse(
            Hash::check('BaoTest2026', $user->fresh()->password)
        );
    }

    public function test_new_avatar_replaces_old_file(): void
    {
        Storage::fake('public');

        Storage::disk('public')->put('avatars/old.jpg', 'old content');

        $user = User::factory()->create([
            'avatar_path' => 'avatars/old.jpg',
        ]);

        $this->actingAs($user)->post('/profile/avatar', [
            'avatar' => UploadedFile::fake()->image('avatar.jpg', 100, 100),
        ])->assertSessionHasNoErrors();

        $path = $user->fresh()->avatar_path;

        $this->assertNotNull($path);
        Storage::disk('public')->assertExists($path);
        Storage::disk('public')->assertMissing('avatars/old.jpg');
    }

    public function test_non_image_avatar_is_rejected(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        $this->actingAs($user)->post('/profile/avatar', [
            'avatar' => UploadedFile::fake()->create(
                'document.pdf',
                100,
                'application/pdf'
            ),
        ])->assertSessionHasErrors(['avatar'], null, 'avatar');

        $this->assertNull($user->fresh()->avatar_path);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }
}
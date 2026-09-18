<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_guest_can_view_login_page(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('Đăng nhập');
    }

    public function test_guest_cannot_access_dashboard(): void
    {
        $this->get('/dashboard')
            ->assertRedirect('/login');

        $this->assertGuest();
    }

    public function test_user_can_login_with_correct_credentials(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('BaoTest2026'),
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'BaoTest2026',
        ])
            ->assertSessionHasNoErrors()
            ->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($user);

        $this->get('/dashboard')->assertOk();
    }

    public function test_wrong_password_is_rejected(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('BaoTest2026'),
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'WrongPassword2026',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_authenticated_user_is_redirected_from_login(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/login')
            ->assertRedirect('/dashboard');
    }

    public function test_user_can_logout(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/logout')
            ->assertRedirect('/login')
            ->assertSessionHas('status', 'Bạn đã đăng xuất.');

        $this->assertGuest();

        $this->get('/dashboard')
            ->assertRedirect('/login');
    }

    public function test_remember_me_sets_a_cookie(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('BaoTest2026'),
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'BaoTest2026',
            'remember' => '1',
        ]);

        $response->assertRedirect('/dashboard');

        $response->assertCookie(
            Auth::guard('web')->getRecallerName()
        );

        $this->assertAuthenticatedAs($user);
        $this->assertNotEmpty($user->fresh()->remember_token);
    }

    public function test_login_requests_are_rate_limited(): void
    {
        $credentials = [
            'email' => 'rate-limit@example.com',
            'password' => 'WrongPassword2026',
        ];

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson('/login', $credentials)
                ->assertUnprocessable();
        }

        $this->postJson('/login', $credentials)
            ->assertStatus(429);

        $this->assertGuest();
    }
}
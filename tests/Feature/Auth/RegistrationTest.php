<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_guest_can_view_registration_page(): void
    {
        $this->get('/register')
            ->assertOk()
            ->assertSee('Tạo tài khoản');
    }

    public function test_user_can_register(): void
    {
        $response = $this->post('/register', $this->validData());

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/dashboard');

        $user = User::where('email', 'bao@example.com')->firstOrFail();

        $this->assertAuthenticatedAs($user);

        $this->assertTrue(
            Hash::check('BaoTest2026', $user->password)
        );

        $this->assertDatabaseHas('users', [
            'name' => 'Bao Nguyen',
            'email' => 'bao@example.com',
        ]);
    }

    public function test_duplicate_email_is_rejected(): void
    {
        User::factory()->create([
            'email' => 'bao@example.com',
        ]);

        $this->post('/register', $this->validData())
            ->assertSessionHasErrors('email');

        $this->assertGuest();
        $this->assertDatabaseCount('users', 1);
    }

    public function test_password_confirmation_must_match(): void
    {
        $data = $this->validData();
        $data['password_confirmation'] = 'Different2026';

        $this->post('/register', $data)
            ->assertSessionHasErrors('password');

        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
    }

    public function test_password_without_numbers_is_rejected(): void
    {
        $data = $this->validData();
        $data['password'] = 'OnlyLetters';
        $data['password_confirmation'] = 'OnlyLetters';

        $this->post('/register', $data)
            ->assertSessionHasErrors('password');

        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
    }

    private function validData(): array
    {
        return [
            'name' => 'Bao Nguyen',
            'email' => 'bao@example.com',
            'password' => 'BaoTest2026',
            'password_confirmation' => 'BaoTest2026',
        ];
    }
}
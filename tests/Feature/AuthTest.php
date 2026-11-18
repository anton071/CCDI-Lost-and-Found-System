<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $over = []): array
    {
        return array_merge([
            'name' => 'Ana Reyes', 'email' => 'ana@example.com', 'role' => 'student',
            'password' => 'Str0ngPassw0rd', 'password_confirmation' => 'Str0ngPassw0rd',
        ], $over);
    }

    public function test_user_can_register_and_is_signed_in_as_student(): void
    {
        $this->post('/register', $this->payload())->assertRedirect('/dashboard');
        $user = User::where('email', 'ana@example.com')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertSame(UserRole::Student, $user->role);
        $this->assertNotSame('Str0ngPassw0rd', $user->password);   // hashed
    }

    public function test_nobody_can_register_as_admin_or_inject_role_fields(): void
    {
        $this->post('/register', $this->payload(['role' => 'admin']))->assertSessionHasErrors('role');
        $this->assertDatabaseMissing('users', ['email' => 'ana@example.com']);

        $this->post('/register', $this->payload(['role' => 'teacher', 'is_active' => 0]))->assertRedirect('/dashboard');
        $user = User::where('email', 'ana@example.com')->first();
        $this->assertSame(UserRole::Teacher, $user->role);
        $this->assertTrue($user->is_active);
    }

    public function test_registration_validates_input(): void
    {
        $this->post('/register', $this->payload(['password' => 'short', 'password_confirmation' => 'short']))->assertSessionHasErrors('password');
        $this->post('/register', $this->payload(['email' => 'not-an-email']))->assertSessionHasErrors('email');
        User::factory()->create(['email' => 'dup@example.com']);
        $this->post('/register', $this->payload(['email' => 'dup@example.com']))->assertSessionHasErrors('email');
    }

    public function test_login_and_logout(): void
    {
        $u = User::factory()->create(['email' => 'a@example.com']);
        $this->post('/login', ['email' => 'a@example.com', 'password' => 'password'])->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($u);
        $this->post('/logout')->assertRedirect('/');
        $this->assertGuest();
    }

    public function test_wrong_password_and_deactivated_accounts_cannot_sign_in(): void
    {
        User::factory()->create(['email' => 'a@example.com']);
        User::factory()->inactive()->create(['email' => 'off@example.com']);
        $this->post('/login', ['email' => 'a@example.com', 'password' => 'wrong'])->assertSessionHasErrors('email');
        $this->post('/login', ['email' => 'off@example.com', 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_deactivated_user_with_open_session_is_signed_out(): void
    {
        $u = User::factory()->create();
        $this->actingAs($u);
        $u->forceFill(['is_active' => false])->save();
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_login_is_rate_limited(): void
    {
        User::factory()->create(['email' => 'a@example.com']);
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => 'a@example.com', 'password' => 'bad']);
        }
        $this->post('/login', ['email' => 'a@example.com', 'password' => 'password'])->assertStatus(429);
    }

    public function test_guests_are_redirected_from_protected_pages(): void
    {
        foreach (['/dashboard', '/my/reports', '/claims', '/profile', '/notifications', '/reports/new/lost', '/admin'] as $url) {
            $this->get($url)->assertRedirect('/login');
        }
        $this->post('/reports/new/lost', [])->assertRedirect('/login');
    }

    public function test_public_pages_are_open_to_guests(): void
    {
        $this->get('/')->assertOk()->assertSee('Help the CCDI community');
        $this->get('/items')->assertOk();
        $this->get('/login')->assertOk();
        $this->get('/register')->assertOk();
    }

    public function test_profile_update_cannot_change_role_or_status(): void
    {
        $u = User::factory()->create();
        $this->actingAs($u)->put('/profile', ['name' => 'New Name', 'email' => $u->email, 'role' => 'admin', 'is_active' => 0])->assertSessionHasNoErrors();
        $u->refresh();
        $this->assertSame('New Name', $u->name);
        $this->assertSame(UserRole::Student, $u->role);
        $this->assertTrue($u->is_active);
    }

    public function test_password_change_requires_current_password(): void
    {
        $u = User::factory()->create();
        $this->actingAs($u)->put('/profile/password', ['current_password' => 'nope', 'password' => 'An0therLongPass', 'password_confirmation' => 'An0therLongPass'])->assertSessionHasErrors('current_password');
        $this->actingAs($u)->put('/profile/password', ['current_password' => 'password', 'password' => 'An0therLongPass', 'password_confirmation' => 'An0therLongPass'])->assertSessionHasNoErrors();
    }
}

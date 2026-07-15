<?php

namespace Tests\Feature;

use App\Livewire\Auth\Login;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create([
            'email' => 'admin@dev-memory.test',
            'password' => Hash::make('senha-super-secreta'),
        ]);
    }

    public function test_guest_is_redirected_to_login_from_protected_routes(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->get(route('memories.index'))->assertRedirect(route('login'));
    }

    public function test_login_screen_renders_for_guests(): void
    {
        $this->get(route('login'))->assertOk()->assertSeeLivewire(Login::class);
    }

    public function test_authenticated_user_can_reach_dashboard(): void
    {
        $this->actingAs($this->admin())->get(route('dashboard'))->assertOk();
    }

    public function test_valid_credentials_log_in_and_redirect_to_dashboard(): void
    {
        $this->admin();

        Livewire::test(Login::class)
            ->set('email', 'admin@dev-memory.test')
            ->set('password', 'senha-super-secreta')
            ->call('login')
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticated();
    }

    public function test_invalid_credentials_do_not_authenticate(): void
    {
        $this->admin();

        Livewire::test(Login::class)
            ->set('email', 'admin@dev-memory.test')
            ->set('password', 'senha-errada')
            ->call('login')
            ->assertHasErrors('email');

        $this->assertGuest();
    }

    public function test_login_requires_email_and_password(): void
    {
        Livewire::test(Login::class)
            ->set('email', '')
            ->set('password', '')
            ->call('login')
            ->assertHasErrors(['email', 'password']);
    }

    public function test_user_can_logout(): void
    {
        $this->actingAs($this->admin())
            ->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_make_admin_command_creates_user(): void
    {
        $this->artisan('memory:make-admin', ['--email' => 'novo@admin.test', '--name' => 'Novo Admin'])
            ->expectsQuestion('Senha (mínimo 8 caracteres)', 'outra-senha-forte')
            ->assertSuccessful();

        $user = User::firstWhere('email', 'novo@admin.test');
        $this->assertNotNull($user);
        $this->assertTrue(Hash::check('outra-senha-forte', $user->password));
    }
}

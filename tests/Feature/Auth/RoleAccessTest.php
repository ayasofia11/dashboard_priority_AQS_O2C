<?php

namespace Tests\Feature\Auth;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;   // base vide et remigrée avant chaque test

    // Fabrique un utilisateur avec le rôle voulu (la factory ignore $fillable).
    private function userWithRole(Role $role, bool $active = true): User
    {
        return User::factory()->create(['role' => $role, 'is_active' => $active]);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = $this->userWithRole(Role::Planner);

        // 'password' est le mot de passe par défaut de la factory.
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($user);
    }

    public function test_wrong_password_is_rejected(): void
    {
        $user = $this->userWithRole(Role::Planner);

        $this->post('/login', ['email' => $user->email, 'password' => 'faux'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_inactive_user_cannot_login(): void
    {
        $user = $this->userWithRole(Role::Planner, active: false);

        $this->post('/login', ['email' => $user->email, 'password' => 'password']);

        $this->assertGuest();
    }

    public function test_logout_works(): void
    {
        $this->actingAs($this->userWithRole(Role::Planner))
            ->post('/logout')
            ->assertRedirect('/login');

        $this->assertGuest();
    }

    // T09 : le lecteur tente d'importer -> accès refusé (403).
    public function test_reader_cannot_open_import_form(): void
    {
        $this->actingAs($this->userWithRole(Role::Reader))
            ->get('/imports/create')
            ->assertForbidden();
    }

    public function test_reader_cannot_post_customer_import(): void
{
    $this->actingAs($this->userWithRole(Role::Reader))
        ->post('/imports/customers')
        ->assertForbidden();
}

    public function test_planner_can_open_import_form(): void
    {
        $this->actingAs($this->userWithRole(Role::Planner))
            ->get('/imports/create')
            ->assertOk();
    }

    public function test_reader_cannot_export_but_commercial_can(): void
    {
        $this->actingAs($this->userWithRole(Role::Reader))
            ->get('/orders/export')->assertForbidden();

        $this->actingAs($this->userWithRole(Role::Commercial))
            ->get('/orders/export')->assertOk();
    }

    public function test_only_admin_can_open_admin_area(): void
    {
        $this->actingAs($this->userWithRole(Role::Planner))
            ->get('/admin/users')->assertForbidden();

        $this->actingAs($this->userWithRole(Role::Admin))
            ->get('/admin/users')->assertOk();
    }
}

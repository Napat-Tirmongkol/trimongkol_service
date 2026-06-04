<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSessionSeparationTest extends TestCase
{
    use RefreshDatabase;

    private function createAdminUser(): User
    {
        $role = Role::create([
            'name' => 'Super Admin',
            'key' => Role::SUPER,
            'is_system' => true,
            'permissions' => ['*'],
        ]);

        return User::factory()->create([
            'is_admin' => true,
            'role_id' => $role->id,
            'is_active' => true,
        ]);
    }

    public function test_admin_logging_in_via_regular_login_gets_user_context_and_is_blocked_from_admin_panel(): void
    {
        $admin = $this->createAdminUser();

        $response = $this->post('/login', [
            'email' => $admin->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertEquals('user', session('auth_context'));

        // Admin who logged in via regular login cannot access admin dashboard (gets 403)
        $adminDashboardResponse = $this->get(route('admin.dashboard'));
        $adminDashboardResponse->assertStatus(403);
    }

    public function test_admin_logging_in_via_admin_login_gets_admin_context_and_is_blocked_from_user_portal(): void
    {
        $admin = $this->createAdminUser();

        $response = $this->post(route('admin.login.store'), [
            'email' => $admin->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('admin.dashboard'));
        $this->assertEquals('admin', session('auth_context'));

        // Admin who logged in via admin login gets redirected to admin dashboard when trying to view regular dashboard
        $userDashboardResponse = $this->get(route('dashboard'));
        $userDashboardResponse->assertRedirect(route('admin.dashboard'));
    }

    public function test_impersonation_changes_context_and_stopping_restores_it(): void
    {
        $admin = $this->createAdminUser();
        $client = User::factory()->create(['is_admin' => false, 'is_active' => true]);

        // 1. Log in as admin via admin login
        $this->post(route('admin.login.store'), [
            'email' => $admin->email,
            'password' => 'password',
        ]);
        $this->assertEquals('admin', session('auth_context'));

        // 2. Start impersonating client
        $impersonateResponse = $this->post(route('admin.users.impersonate', $client));
        $impersonateResponse->assertRedirect(route('dashboard'));
        $this->assertEquals('user', session('auth_context'));

        // 3. Stop impersonation
        $stopResponse = $this->post(route('impersonate.stop'));
        $stopResponse->assertRedirect(route('admin.dashboard'));
        $this->assertEquals('admin', session('auth_context'));
    }
}

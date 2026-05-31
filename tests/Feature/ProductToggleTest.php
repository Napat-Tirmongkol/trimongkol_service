<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use App\Models\Workspace;
use App\Services\ProductGate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProductToggleTest extends TestCase
{
    use RefreshDatabase;

    private function actingMember(): User
    {
        $user = User::factory()->create();
        $workspace = Workspace::create(['name' => 'ACME Co.', 'slug' => 'acme-'.Str::random(6)]);
        $workspace->members()->attach($user->id, ['role' => 'owner', 'joined_at' => now()]);
        $this->actingAs($user);

        return $user;
    }

    private function actingAdmin(): User
    {
        $user = User::factory()->create(['is_admin' => true]);
        $workspace = Workspace::create(['name' => 'ACME Admin', 'slug' => 'adm-'.Str::random(6)]);
        $workspace->members()->attach($user->id, ['role' => 'owner', 'joined_at' => now()]);
        $this->actingAs($user);

        return $user;
    }

    private function disable(string $product): void
    {
        Setting::updateOrCreate(['key' => "products.{$product}.enabled"], ['value' => '0']);
    }

    public function test_products_are_enabled_by_default(): void
    {
        $this->assertTrue(ProductGate::enabled('accounting'));
        $this->assertTrue(ProductGate::enabled('queue'));
    }

    public function test_setting_zero_disables_a_product(): void
    {
        $this->disable('accounting');
        $this->assertFalse(ProductGate::enabled('accounting'));
        $this->assertTrue(ProductGate::enabled('queue')); // others unaffected
    }

    public function test_blank_value_keeps_a_product_enabled(): void
    {
        Setting::updateOrCreate(['key' => 'products.accounting.enabled'], ['value' => '']);
        $this->assertTrue(ProductGate::enabled('accounting'));
    }

    public function test_accounting_routes_are_blocked_when_disabled(): void
    {
        $this->actingMember();

        $this->get(route('accounting.dashboard'))->assertOk();   // on by default

        $this->disable('accounting');
        $this->get(route('accounting.dashboard'))->assertNotFound();
        $this->get(route('accounting.reports'))->assertNotFound();
    }

    public function test_queue_routes_are_blocked_when_disabled(): void
    {
        $this->actingMember();

        $this->get(route('queues.index'))->assertOk();

        $this->disable('queue');
        $this->get(route('queues.index'))->assertNotFound();
    }

    public function test_nav_hides_a_disabled_product(): void
    {
        $this->actingMember();

        $this->get(route('dashboard'))->assertOk()->assertSee(route('accounting.dashboard'));

        $this->disable('accounting');
        $this->get(route('dashboard'))->assertOk()->assertDontSee(route('accounting.dashboard'));
    }

    public function test_is_on_is_the_raw_switch_ignoring_the_viewer(): void
    {
        $this->assertTrue(ProductGate::isOn('accounting'));
        $this->disable('accounting');
        $this->assertFalse(ProductGate::isOn('accounting'));
    }

    public function test_admin_can_preview_a_disabled_product(): void
    {
        $this->actingAdmin();
        $this->disable('accounting');

        // Off for everyone, but the admin still gets through to test it.
        $this->assertFalse(ProductGate::isOn('accounting'));
        $this->assertTrue(ProductGate::enabled('accounting'));
        $this->get(route('accounting.dashboard'))->assertOk();
        $this->get(route('dashboard'))->assertOk()->assertSee(route('accounting.dashboard'));
    }
}

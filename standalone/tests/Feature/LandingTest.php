<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class LandingTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_sees_the_landing_page_with_pricing(): void
    {
        $this->get('/')->assertOk()
            ->assertSee(__('app.landing.hero_title'))
            ->assertSee('Free')
            ->assertSee('Pro')
            ->assertSee('Business')
            ->assertSee(route('register'), false);
    }

    public function test_signed_in_user_is_redirected_to_the_app(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::create(['name' => 'ACME', 'slug' => 'acme-'.Str::random(6)]);
        $workspace->members()->attach($user->id, ['role' => 'owner', 'joined_at' => now()]);

        $this->actingAs($user)->get('/')->assertRedirect(route('accounting.dashboard'));
    }

    public function test_pricing_anchor_and_ctas_point_to_register(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('id="pricing"', false)
            ->assertSee(__('app.landing.cta_primary'));
    }
}

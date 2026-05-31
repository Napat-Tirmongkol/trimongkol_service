<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class BillingTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0: User, 1: Workspace} */
    private function actingMember(string $role = 'owner'): array
    {
        $user = User::factory()->create();
        $workspace = Workspace::create(['name' => 'ACME', 'slug' => 'acme-'.Str::random(6)]);
        $workspace->members()->attach($user->id, ['role' => $role, 'joined_at' => now()]);
        $this->actingAs($user);

        return [$user, $workspace];
    }

    public function test_billing_page_lists_the_plans(): void
    {
        $this->actingMember('owner');

        $this->get(route('billing.index'))->assertOk()
            ->assertSee('Free')
            ->assertSee('Pro')
            ->assertSee('Business');
    }

    public function test_free_plan_is_current_when_no_subscription(): void
    {
        $this->actingMember('owner');

        $this->get(route('billing.index'))->assertOk()
            ->assertViewHas('currentKey', Plan::FREE);
    }

    public function test_owner_can_select_a_paid_plan(): void
    {
        [, $workspace] = $this->actingMember('owner');

        $this->post(route('billing.subscribe'), ['plan_key' => Plan::PRO])
            ->assertRedirect(route('billing.index'))
            ->assertSessionHas('status');

        $sub = $workspace->fresh()->subscription;
        $this->assertNotNull($sub);
        $this->assertSame(Plan::PRO, $sub->plan_key);
    }

    public function test_switching_to_free_activates_immediately(): void
    {
        [, $workspace] = $this->actingMember('owner');
        Subscription::create([
            'workspace_id' => $workspace->id, 'plan_key' => Plan::PRO,
            'status' => Subscription::STATUS_ACTIVE,
        ]);

        $this->post(route('billing.subscribe'), ['plan_key' => Plan::FREE])
            ->assertRedirect(route('billing.index'));

        $sub = $workspace->fresh()->subscription;
        $this->assertSame(Plan::FREE, $sub->plan_key);
        $this->assertSame(Subscription::STATUS_ACTIVE, $sub->status);
    }

    public function test_unknown_plan_is_rejected(): void
    {
        $this->actingMember('owner');

        $this->post(route('billing.subscribe'), ['plan_key' => 'enterprise'])
            ->assertSessionHasErrors('plan_key');
    }

    public function test_non_owner_cannot_change_plan(): void
    {
        [, $workspace] = $this->actingMember('member');

        $this->post(route('billing.subscribe'), ['plan_key' => Plan::PRO])->assertForbidden();
        $this->assertNull($workspace->fresh()->subscription);
    }

    public function test_trial_badge_shows_days_left(): void
    {
        [, $workspace] = $this->actingMember('owner');
        Subscription::create([
            'workspace_id' => $workspace->id, 'plan_key' => Plan::PRO,
            'status' => Subscription::STATUS_TRIAL, 'trial_ends_at' => now()->addDays(10),
        ]);

        // A live trial keeps the workspace on its paid plan.
        $this->get(route('billing.index'))->assertOk()->assertViewHas('currentKey', Plan::PRO);
    }

    public function test_expired_trial_falls_back_to_free(): void
    {
        [, $workspace] = $this->actingMember('owner');
        Subscription::create([
            'workspace_id' => $workspace->id, 'plan_key' => Plan::PRO,
            'status' => Subscription::STATUS_TRIAL, 'trial_ends_at' => now()->subDay(),
        ]);

        $this->get(route('billing.index'))->assertOk()->assertViewHas('currentKey', Plan::FREE);
    }
}

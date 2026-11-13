<?php

namespace Tests\Feature\Portfolio;

use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class JourneyTest extends TestCase
{
    private array $backups = [];

    protected function setUp(): void
    {
        parent::setUp();

        foreach ([
            storage_path('app/portfolio_journey_regions.json'),
            storage_path('app/private/portfolio_journey_regions.json'),
        ] as $path) {
            if (file_exists($path)) {
                $this->backups[$path] = file_get_contents($path);
            }
        }
    }

    protected function tearDown(): void
    {
        foreach ([
            storage_path('app/portfolio_journey_regions.json'),
            storage_path('app/private/portfolio_journey_regions.json'),
        ] as $path) {
            if (array_key_exists($path, $this->backups)) {
                file_put_contents($path, $this->backups[$path], LOCK_EX);
            } elseif (file_exists($path)) {
                @unlink($path);
            }
        }

        parent::tearDown();
    }

    private function allowedUser(): User
    {
        $allowed = (array) config('portfolio.allowed_emails', ['napatnom@gmail.com']);
        $email = $allowed[0] ?? 'napatnom@gmail.com';

        return User::factory()->make(['id' => 999, 'email' => $email]);
    }

    public function test_unauthenticated_html_request_redirects_to_portfolio_login(): void
    {
        $response = $this->get('/portfolio/journey');

        $response->assertStatus(302);
        $response->assertRedirect(route('portfolio.login'));
    }

    public function test_unauthenticated_json_request_returns_401(): void
    {
        $response = $this->postJson('/portfolio/journey/regions/toggle', [
            'type'    => 'province',
            'code'    => '50',
            'is_auto' => false,
        ]);

        $response->assertStatus(401)
            ->assertJson(['message' => 'Unauthenticated.']);
    }

    public function test_disallowed_authenticated_user_receives_403(): void
    {
        $outsider = User::factory()->make(['id' => 998, 'email' => 'outsider@example.com']);

        $this->actingAs($outsider)
            ->get('/portfolio/journey')
            ->assertStatus(403);
    }

    public function test_allowed_authenticated_user_renders_journey_page(): void
    {
        $response = $this->actingAs($this->allowedUser())
            ->get('/portfolio/journey');

        $response->assertStatus(200)
            ->assertSee('id="journey-map"', false)
            ->assertSee('แผนที่พิชิตประเทศไทย', false)
            ->assertSee('thailand_provinces.geojson', false);
    }

    public function test_authenticated_toggle_province_persists_to_storage(): void
    {
        $response = $this->actingAs($this->allowedUser())
            ->postJson('/portfolio/journey/regions/toggle', [
                'type'    => 'province',
                'code'    => '50',
                'is_auto' => false,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('ok', true);

        $saved = json_decode(file_get_contents(storage_path('app/portfolio_journey_regions.json')), true);
        $this->assertContains('50', $saved['manual_provinces'] ?? []);
    }

    public function test_authenticated_toggle_amphoe_persists_to_storage(): void
    {
        $response = $this->actingAs($this->allowedUser())
            ->postJson('/portfolio/journey/regions/toggle', [
                'type'    => 'amphoe',
                'code'    => '5001',
                'is_auto' => false,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('ok', true);

        $saved = json_decode(file_get_contents(storage_path('app/portfolio_journey_regions.json')), true);
        $this->assertContains('5001', $saved['manual_amphoes'] ?? []);
    }

    public function test_authenticated_color_scheme_persists_to_storage(): void
    {
        $response = $this->actingAs($this->allowedUser())
            ->postJson('/portfolio/journey/regions/toggle', [
                'type'         => 'color',
                'color_scheme' => 'violet',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('ok', true)
            ->assertJsonPath('regions.color_scheme', 'violet');

        $saved = json_decode(file_get_contents(storage_path('app/portfolio_journey_regions.json')), true);
        $this->assertSame('violet', $saved['color_scheme'] ?? null);
    }

    public function test_invalid_toggle_payload_returns_422(): void
    {
        $user = $this->allowedUser();

        $this->actingAs($user)
            ->postJson('/portfolio/journey/regions/toggle', ['type' => 'invalid'])
            ->assertStatus(422);

        $this->actingAs($user)
            ->postJson('/portfolio/journey/regions/toggle', ['type' => 'color', 'color_scheme' => 'neon'])
            ->assertStatus(422);

        $this->actingAs($user)
            ->postJson('/portfolio/journey/regions/toggle', ['type' => 'province', 'code' => ''])
            ->assertStatus(422);
    }

    public function test_geojson_assets_exist_and_contain_expected_feature_counts(): void
    {
        $provinces = json_decode(file_get_contents(public_path('thailand_provinces.geojson')), true);
        $amphoes = json_decode(file_get_contents(public_path('thailand_amphoes.geojson')), true);

        $this->assertIsArray($provinces['features'] ?? null);
        $this->assertIsArray($amphoes['features'] ?? null);
        $this->assertCount(77, $provinces['features']);
        $this->assertCount(928, $amphoes['features']);
    }
}

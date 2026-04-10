<?php

namespace Tests\Feature;

use App\Services\Routing\DijkstraRoutingService;
use App\Services\Sessions\SessionManager;
use Tests\TestCase;

class RouteApiTest extends TestCase
{
    protected function tearDown(): void
    {
        app(SessionManager::class)->flush();

        parent::tearDown();
    }

    public function test_it_returns_a_single_mode_route(): void
    {
        $response = $this->postJson('/api/route', [
            'start' => 'farmgate',
            'destination' => 'gulshan',
            'allowed_modes' => ['car'],
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.selected_modes.0', 'car')
            ->assertJsonPath('data.path.0', 'farmgate')
            ->assertJsonPath('data.nodes.0', 'farmgate')
            ->assertJsonPath('data.path.3', 'gulshan')
            ->assertJsonPath('data.total_cost', 12)
            ->assertJsonCount(3, 'data.segments')
            ->assertJsonCount(3, 'data.route_segments')
            ->assertJsonPath('data.switches', 0)
            ->assertJsonPath('data.justification.mode_switches', 0)
            ->assertJsonStructure([
                'data' => [
                    'session_id',
                    'computation_time_ms',
                ],
            ])
            ->assertJsonPath('data.session_saved', true);
    }

    public function test_it_refuses_to_use_edges_that_do_not_allow_the_mode(): void
    {
        $response = $this->postJson('/api/route', [
            'start' => 'farmgate',
            'destination' => 'green_road',
            'allowed_modes' => ['car'],
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.path', [
                'farmgate',
                'karwan_bazar',
                'green_road',
            ])
            ->assertJsonPath('data.total_cost', 12);
    }

    public function test_it_can_switch_modes_at_transfer_nodes_when_that_is_cheaper(): void
    {
        $response = $this->postJson('/api/route', [
            'start' => 'farmgate',
            'destination' => 'gulshan',
            'allowed_modes' => ['car', 'rickshaw', 'walk'],
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.path', [
                'farmgate',
                'green_road',
                'gulshan',
            ])
            ->assertJsonPath('data.total_cost', 12)
            ->assertJsonPath('data.switches', 1)
            ->assertJsonPath('data.justification.mode_switches', 1)
            ->assertJsonPath('data.justification.mode_switch_penalty_applied', 3);
    }

    public function test_it_saves_a_session_when_session_id_is_supplied(): void
    {
        $response = $this->postJson('/api/route', [
            'session_id' => 'session-1',
            'start' => 'farmgate',
            'destination' => 'gulshan',
            'allowed_modes' => ['car', 'rickshaw', 'walk'],
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.session_id', 'session-1')
            ->assertJsonPath('data.session_saved', true);

        $session = app(SessionManager::class)->getSession('session-1');

        $this->assertNotNull($session);
        $this->assertSame('farmgate', $session['request']['start']);
        $this->assertSame('gulshan', $session['request']['destination']);
    }

    public function test_it_auto_generates_and_saves_a_session_when_session_id_is_missing(): void
    {
        $response = $this->postJson('/api/route', [
            'start' => 'farmgate',
            'destination' => 'gulshan',
            'allowed_modes' => ['car', 'rickshaw', 'walk'],
        ]);

        $sessionId = $response->json('data.session_id');

        $response
            ->assertOk()
            ->assertJsonPath('data.session_saved', true);

        $this->assertNotEmpty($sessionId);
        $this->assertNotNull(app(SessionManager::class)->getSession($sessionId));
    }

    public function test_it_reroutes_only_impacted_sessions(): void
    {
        $this->postJson('/api/route', [
            'session_id' => 'session-hit',
            'start' => 'farmgate',
            'destination' => 'gulshan',
            'allowed_modes' => ['car'],
        ]);

        $this->postJson('/api/route', [
            'session_id' => 'session-safe',
            'start' => 'farmgate',
            'destination' => 'green_road',
            'allowed_modes' => ['walk'],
        ]);

        $response = $this->postJson('/api/anomaly', [
            'edge_ids' => ['edge_karwan_bazar_tejgaon'],
            'multiplier' => 10,
        ]);

        $response
            ->assertStatus(202)
            ->assertJsonPath('reroute_summary.sessions_rerouted', 1)
            ->assertJsonPath('reroute_summary.sessions.0.session_id', 'session-hit');
    }

    public function test_it_can_make_exactly_two_switches_on_a_two_transfer_route(): void
    {
        $graph = [
            'start' => [
                ['id' => 'edge_start_hub', 'to' => 'hub', 'cost' => 1, 'modes' => ['car']],
                ['id' => 'edge_start_end', 'to' => 'end', 'cost' => 20, 'modes' => ['car']],
            ],
            'hub' => [
                ['id' => 'edge_hub_bridge', 'to' => 'bridge', 'cost' => 1, 'modes' => ['rickshaw']],
            ],
            'bridge' => [
                ['id' => 'edge_bridge_end', 'to' => 'end', 'cost' => 1, 'modes' => ['walk']],
            ],
            'end' => [],
        ];

        config()->set('golitransit.transfer_nodes', ['start', 'hub', 'bridge']);
        config()->set('golitransit.mode_switch_penalty', 1);

        $route = app(DijkstraRoutingService::class)->run($graph, 'start', 'end', ['car', 'rickshaw', 'walk']);

        $this->assertSame(['start', 'hub', 'bridge', 'end'], $route['path']);
        $this->assertSame(2, $route['mode_switches']);
        $this->assertSame(2, $route['mode_switch_penalty_applied']);
    }

    public function test_it_requires_valid_payload_fields(): void
    {
        $response = $this->postJson('/api/route', [
            'start' => 'farmgate',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors([
            'destination',
            'allowed_modes',
        ]);
    }
}

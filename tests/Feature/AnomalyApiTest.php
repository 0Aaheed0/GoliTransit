<?php

namespace Tests\Feature;

use App\Services\Sessions\SessionManager;
use Tests\TestCase;

class AnomalyApiTest extends TestCase
{
    protected function tearDown(): void
    {
        app(SessionManager::class)->flush();

        parent::tearDown();
    }

    public function test_it_updates_graph_weights_by_edge_id(): void
    {
        $response = $this->postJson('/api/anomaly', [
            'edge_ids' => ['edge_karwan_bazar_tejgaon'],
            'multiplier' => 10,
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('affected_edges.0.id', 'edge_karwan_bazar_tejgaon')
            ->assertJsonPath('affected_edges.0.current_weight', 40);
    }

    public function test_it_updates_graph_weights_by_bounding_box(): void
    {
        $response = $this->postJson('/api/anomaly', [
            'edge_ids' => [],
            'multiplier' => 10,
            'bounding_box' => [
                'min_lat' => 23.75,
                'max_lat' => 23.79,
                'min_lng' => 90.39,
                'max_lng' => 90.42,
            ],
        ]);

        $response
            ->assertOk()
            ->assertJsonStructure([
                'affected_edges' => [
                    ['id', 'current_weight'],
                ],
                'meta' => ['updated_edges'],
            ]);
    }

    public function test_graph_snapshot_returns_enriched_metadata(): void
    {
        $response = $this->getJson('/api/graph/snapshot');

        $response
            ->assertOk()
            ->assertJsonStructure([
                'data' => ['nodes', 'edges'],
                'meta' => ['node_count', 'edge_count', 'goli_edge_count', 'overpass_node_count'],
            ]);
    }

    public function test_api_validation_errors_return_clean_json(): void
    {
        $response = $this->postJson('/api/anomaly', [
            'multiplier' => 10,
        ]);

        $response
            ->assertStatus(400)
            ->assertJsonStructure([
                'error',
                'details',
            ]);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Graph\GraphManager;
use Illuminate\Http\JsonResponse;

class GraphSnapshotController extends Controller
{
    public function __invoke(GraphManager $graphManager): JsonResponse
    {
        $graph = $graphManager->getGraph();
        $nodes = $graph['nodes'];
        $edges = $graph['edges'];

        return response()->json([
            'data' => [
                'nodes' => $nodes,
                'edges' => $edges,
            ],
            'meta' => [
                'source' => 'graph_manager',
                'node_count' => count($nodes),
                'edge_count' => count($edges),
                'goli_edge_count' => count(array_filter($edges, fn (array $edge): bool => $edge['is_goli'])),
                'overpass_node_count' => count(array_filter($nodes, fn (array $node): bool => $node['type'] === 'overpass')),
                'note' => 'Snapshot of the current graph, including any anomaly-inflated current weights.',
            ],
        ]);
    }
}

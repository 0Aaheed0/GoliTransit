<?php

namespace App\Http\Controllers;

use App\Services\Graph\GraphManager;
use App\Services\Routing\DijkstraRoutingService;

class WelcomeController extends Controller
{
    public function index(GraphManager $graphManager, DijkstraRoutingService $routingService)
    {
        $graph = $graphManager->getGraph();
        $adjacencyGraph = $graphManager->getAdjacencyGraph();
        $sampleRoute = null;
        $sampleRouteError = null;

        try {
            $sampleRoute = $routingService->run(
                $adjacencyGraph,
                'farmgate',
                'gulshan_2',
                ['car', 'rickshaw', 'walk']
            );
        } catch (\Throwable $exception) {
            $sampleRouteError = $exception->getMessage();
        }

        return view('debug-home', [
            'nodeCount' => count($graph['nodes']),
            'edgeCount' => count($graph['edges']),
            'goliEdgeCount' => count(array_filter($graph['edges'], fn (array $edge): bool => $edge['is_goli'])),
            'overpassNodeCount' => count(array_filter($graph['nodes'], fn (array $node): bool => $node['type'] === 'overpass')),
            'nodes' => $graph['nodes'],
            'edges' => $graph['edges'],
            'sampleRoute' => $sampleRoute,
            'sampleRouteError' => $sampleRouteError,
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Routing\DemoGraphService;
use Illuminate\Http\JsonResponse;

class GraphSnapshotController extends Controller
{
    public function __invoke(DemoGraphService $graphService): JsonResponse
    {
        return response()->json([
            'data' => [
                'nodes' => $graphService->getNodes(),
                'edges' => $graphService->getEdges(),
            ],
            'meta' => [
                'source' => 'demo_graph',
                'note' => 'This snapshot is the current contract baseline for Member B and Member C.',
            ],
        ]);
    }
}

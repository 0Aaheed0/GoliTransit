<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Graph\GraphManager;
use App\Services\Sessions\SessionManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnomalyController extends Controller
{
    public function __invoke(
        Request $request,
        GraphManager $graphManager,
        SessionManager $sessionManager
    ): JsonResponse
    {
        $validated = $request->validate([
            'edge_ids' => ['sometimes', 'array'],
            'edge_ids.*' => ['required', 'string'],
            'multiplier' => ['required', 'numeric', 'min:1'],
            'bounding_box' => ['sometimes', 'array'],
            'bounding_box.min_lat' => ['required_with:bounding_box', 'numeric'],
            'bounding_box.max_lat' => ['required_with:bounding_box', 'numeric'],
            'bounding_box.min_lng' => ['required_with:bounding_box', 'numeric'],
            'bounding_box.max_lng' => ['required_with:bounding_box', 'numeric'],
        ]);

        $edgeIds = $validated['edge_ids'] ?? [];
        $boundingBox = $validated['bounding_box'] ?? null;

        if ($edgeIds === [] && $boundingBox === null) {
            return response()->json([
                'error' => 'Provide either edge_ids or bounding_box.',
            ], 400);
        }

        $affectedEdges = $graphManager->updateAnomalyZoneWithBoundingBox(
            $edgeIds,
            $validated['multiplier'],
            $boundingBox
        );
        $affectedEdgeIds = array_values(array_map(
            static fn (array $edge): string => $edge['id'],
            $affectedEdges
        ));
        $reroutedSessions = $sessionManager->rerouteAffectedSessions($affectedEdgeIds);

        return response()->json([
            'message' => 'Anomaly applied successfully.',
            'contract' => [
                'edge_ids' => $edgeIds,
                'multiplier' => $validated['multiplier'],
                'bounding_box' => $boundingBox,
            ],
            'reroute_summary' => [
                'affected_edge_ids' => $affectedEdgeIds,
                'sessions_rerouted' => count($reroutedSessions),
                'sessions' => $reroutedSessions,
            ],
            'affected_edges' => array_map(static function (array $edge): array {
                return [
                    'id' => $edge['id'],
                    'from' => $edge['from'],
                    'to' => $edge['to'],
                    'base_weight' => $edge['base_weight'],
                    'current_weight' => $edge['current_weight'],
                ];
            }, $affectedEdges),
            'meta' => [
                'updated_edges' => count($affectedEdges),
            ],
        ]);
    }
}

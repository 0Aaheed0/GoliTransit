<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Sessions\SessionManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnomalyController extends Controller
{
    public function __invoke(
        Request $request,
        SessionManager $sessionManager
    ): JsonResponse
    {
        $validated = $request->validate([
            'edge_ids' => ['required', 'array', 'min:1'],
            'edge_ids.*' => ['required', 'string'],
            'multiplier' => ['required', 'numeric', 'min:1'],
        ]);

        $reroutedSessions = $sessionManager->rerouteAffectedSessions($validated['edge_ids']);

        return response()->json([
            'message' => 'Anomaly weight updates are still reserved for Step C3, but session rerouting is now wired.',
            'contract' => [
                'edge_ids' => $validated['edge_ids'],
                'multiplier' => $validated['multiplier'],
            ],
            'reroute_summary' => [
                'affected_edge_ids' => $validated['edge_ids'],
                'sessions_rerouted' => count($reroutedSessions),
                'sessions' => $reroutedSessions,
            ],
            'next_owner' => 'Member C',
            'remaining_step_c3_work' => [
                'update graph weights for the supplied edge IDs',
                'return changed weights in the anomaly response',
            ],
        ], 202);
    }
}

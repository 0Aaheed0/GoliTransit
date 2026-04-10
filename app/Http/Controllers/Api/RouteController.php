<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Routing\DemoGraphService;
use App\Services\Routing\DijkstraRoutingService;
use App\Services\Sessions\SessionManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use RuntimeException;

class RouteController extends Controller
{
    public function __invoke(
        Request $request,
        DemoGraphService $graphService,
        DijkstraRoutingService $routingService,
        SessionManager $sessionManager
    ): JsonResponse {
        $startedAt = hrtime(true);
        $graph = $graphService->getGraph();
        $nodes = $graphService->getNodes();

        $validated = $request->validate([
            'start' => ['required', 'string', Rule::in($nodes)],
            'destination' => ['required', 'string', Rule::in($nodes)],
            'allowed_modes' => ['required', 'array', 'min:1'],
            'allowed_modes.*' => ['required', 'string', Rule::in(['car', 'rickshaw', 'walk'])],
            'session_id' => ['nullable', 'string', 'max:100'],
        ]);

        $sessionId = $validated['session_id'] ?? (string) Str::uuid();

        try {
            $route = $routingService->run(
                $graph,
                $validated['start'],
                $validated['destination'],
                $validated['allowed_modes']
            );
        } catch (RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }

        $route['request'] = [
            'start' => $validated['start'],
            'destination' => $validated['destination'],
            'allowed_modes' => $validated['allowed_modes'],
        ];

        $sessionManager->createSession($sessionId, $route);

        $computationTimeMs = (int) round((hrtime(true) - $startedAt) / 1_000_000);
        $travelSegments = array_values(array_filter(
            $route['segments'],
            static fn (array $segment): bool => $segment['type'] === 'travel'
        ));

        return response()->json([
            'data' => [
                'start' => $validated['start'],
                'destination' => $validated['destination'],
                'allowed_modes' => $validated['allowed_modes'],
                'session_id' => $sessionId,
                'selected_modes' => $route['selected_modes'],
                'path' => $route['path'],
                'nodes' => $route['path'],
                'segments' => $route['segments'],
                'route_segments' => $travelSegments,
                'total_cost' => $route['total_cost'],
                'switches' => $route['mode_switches'],
                'computation_time_ms' => $computationTimeMs,
                'justification' => [
                    'summary' => 'Best available route on the current demo graph using the selected travel modes.',
                    'mode_switches' => $route['mode_switches'],
                    'mode_switch_penalty_applied' => $route['mode_switch_penalty_applied'],
                    'note' => 'This is the Step A3 multi-modal routing baseline with designated transfer nodes and switch penalties.',
                ],
                'session_saved' => true,
            ],
        ]);
    }
}

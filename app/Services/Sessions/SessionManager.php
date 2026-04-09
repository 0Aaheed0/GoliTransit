<?php

namespace App\Services\Sessions;

use App\Services\Routing\DemoGraphService;
use App\Services\Routing\DijkstraRoutingService;

class SessionManager
{
    protected static array $sessions = [];

    public function __construct(
        protected DemoGraphService $graphService,
        protected DijkstraRoutingService $routingService
    ) {
    }

    public function createSession(string $sessionId, array $routeResult): array
    {
        $request = $routeResult['request'] ?? [];

        static::$sessions[$sessionId] = [
            'session_id' => $sessionId,
            'request' => [
                'start' => $request['start'] ?? null,
                'destination' => $request['destination'] ?? null,
                'allowed_modes' => $request['allowed_modes'] ?? [],
            ],
            'route' => $routeResult,
        ];

        return static::$sessions[$sessionId];
    }

    public function getSession(string $sessionId): ?array
    {
        return static::$sessions[$sessionId] ?? null;
    }

    public function allSessions(): array
    {
        return array_values(static::$sessions);
    }

    public function rerouteAffectedSessions(array $affectedEdges): array
    {
        $rerouted = [];
        $graph = $this->graphService->getGraph();

        foreach (static::$sessions as $sessionId => $session) {
            $route = $session['route'];
            $routeEdges = array_values(array_filter(array_map(
                static fn (array $segment): ?string => $segment['edge_id'],
                $route['segments']
            )));

            if (array_intersect($affectedEdges, $routeEdges) === []) {
                continue;
            }

            $updatedRoute = $this->routingService->run(
                $graph,
                $session['request']['start'],
                $session['request']['destination'],
                $session['request']['allowed_modes']
            );

            $updatedRoute['request'] = $session['request'];
            static::$sessions[$sessionId]['route'] = $updatedRoute;

            $rerouted[] = [
                'session_id' => $sessionId,
                'request' => $session['request'],
                'route' => $updatedRoute,
            ];
        }

        return $rerouted;
    }

    public function flush(): void
    {
        static::$sessions = [];
    }
}

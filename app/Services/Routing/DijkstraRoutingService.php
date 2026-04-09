<?php

namespace App\Services\Routing;

use RuntimeException;

class DijkstraRoutingService
{
    public function run(array $graph, string $start, string $end, array $modes): array
    {
        if (! isset($graph[$start])) {
            throw new RuntimeException("Unknown start node [{$start}].");
        }

        if (! isset($graph[$end])) {
            throw new RuntimeException("Unknown destination node [{$end}].");
        }

        if ($modes === []) {
            throw new RuntimeException('At least one travel mode must be provided.');
        }

        $transferNodes = config('golitransit.transfer_nodes', []);
        $switchPenalty = (int) config('golitransit.mode_switch_penalty', 3);
        $distances = [];
        $previous = [];
        $visited = [];

        foreach ($graph as $node => $_edges) {
            foreach ($modes as $mode) {
                $stateKey = $this->stateKey($node, $mode);
                $distances[$stateKey] = INF;
                $previous[$stateKey] = null;
                $visited[$stateKey] = false;
            }
        }

        foreach ($modes as $mode) {
            $distances[$this->stateKey($start, $mode)] = 0;
        }

        while (true) {
            $currentState = $this->getClosestUnvisitedNode($distances, $visited);

            if ($currentState === null) {
                break;
            }

            ['node' => $currentNode, 'mode' => $currentMode] = $this->parseStateKey($currentState);
            $visited[$currentState] = true;

            foreach ($graph[$currentNode] as $edge) {
                if (! in_array($currentMode, $edge['modes'], true)) {
                    continue;
                }

                $neighbor = $edge['to'];
                $neighborState = $this->stateKey($neighbor, $currentMode);
                $candidateDistance = $distances[$currentState] + $edge['cost'];

                if ($candidateDistance < $distances[$neighborState]) {
                    $distances[$neighborState] = $candidateDistance;
                    $previous[$neighborState] = [
                        'edge_id' => $edge['id'],
                        'node' => $currentNode,
                        'mode' => $currentMode,
                        'cost' => $edge['cost'],
                        'switch_penalty' => 0,
                    ];
                }
            }

            if (! in_array($currentNode, $transferNodes, true)) {
                continue;
            }

            foreach ($modes as $nextMode) {
                if ($nextMode === $currentMode) {
                    continue;
                }

                $nextState = $this->stateKey($currentNode, $nextMode);
                $candidateDistance = $distances[$currentState] + $switchPenalty;

                if ($candidateDistance < $distances[$nextState]) {
                    $distances[$nextState] = $candidateDistance;
                    $previous[$nextState] = [
                        'edge_id' => null,
                        'node' => $currentNode,
                        'mode' => $currentMode,
                        'cost' => 0,
                        'switch_penalty' => $switchPenalty,
                    ];
                }
            }
        }

        $bestEndState = null;
        $bestEndDistance = INF;

        foreach ($modes as $mode) {
            $stateKey = $this->stateKey($end, $mode);

            if ($distances[$stateKey] < $bestEndDistance) {
                $bestEndDistance = $distances[$stateKey];
                $bestEndState = $stateKey;
            }
        }

        if ($bestEndState === null || $bestEndDistance === INF) {
            throw new RuntimeException('No route is available for the selected travel modes.');
        }

        $segments = $this->buildSegments($previous, $start, $bestEndState);
        $selectedModes = array_values(array_unique(array_map(
            static fn (array $segment): string => $segment['mode'],
            array_filter($segments, static fn (array $segment): bool => $segment['edge_id'] !== null)
        )));

        return [
            'path' => $this->buildPath($segments, $start),
            'segments' => $segments,
            'total_cost' => $bestEndDistance,
            'selected_modes' => $selectedModes,
            'mode_switches' => count(array_filter(
                $segments,
                static fn (array $segment): bool => $segment['edge_id'] === null
            )),
            'mode_switch_penalty_applied' => array_sum(array_map(
                static fn (array $segment): int => $segment['switch_penalty'],
                $segments
            )),
        ];
    }

    protected function getClosestUnvisitedNode(array $distances, array $visited): ?string
    {
        $closestNode = null;
        $closestDistance = INF;

        foreach ($distances as $node => $distance) {
            if ($visited[$node]) {
                continue;
            }

            if ($distance < $closestDistance) {
                $closestDistance = $distance;
                $closestNode = $node;
            }
        }

        return $closestNode;
    }

    protected function buildPath(array $segments, string $start): array
    {
        $path = [$start];

        foreach ($segments as $segment) {
            if ($segment['edge_id'] === null) {
                continue;
            }

            $path[] = $segment['to'];
        }

        return $path;
    }

    protected function buildSegments(array $previous, string $start, string $endState): array
    {
        $segments = [];
        $cursorState = $endState;

        while ($cursorState !== null) {
            ['node' => $cursorNode, 'mode' => $cursorMode] = $this->parseStateKey($cursorState);

            if ($cursorNode === $start && ($previous[$cursorState] ?? null) === null) {
                break;
            }

            $segment = $previous[$cursorState] ?? null;

            if ($segment === null) {
                break;
            }

            if ($segment['edge_id'] === null) {
                array_unshift($segments, [
                    'edge_id' => null,
                    'from' => $segment['node'],
                    'to' => $cursorNode,
                    'cost' => 0,
                    'mode' => $cursorMode,
                    'previous_mode' => $segment['mode'],
                    'switch_penalty' => $segment['switch_penalty'],
                    'type' => 'mode_switch',
                ]);
            } else {
                array_unshift($segments, [
                    'edge_id' => $segment['edge_id'],
                    'from' => $segment['node'],
                    'to' => $cursorNode,
                    'cost' => $segment['cost'],
                    'mode' => $cursorMode,
                    'previous_mode' => $cursorMode,
                    'switch_penalty' => 0,
                    'type' => 'travel',
                ]);
            }

            $cursorState = $this->stateKey($segment['node'], $segment['mode']);
        }

        return $segments;
    }

    protected function stateKey(string $node, string $mode): string
    {
        return $node.'|'.$mode;
    }

    protected function parseStateKey(string $stateKey): array
    {
        [$node, $mode] = explode('|', $stateKey, 2);

        return [
            'node' => $node,
            'mode' => $mode,
        ];
    }
}

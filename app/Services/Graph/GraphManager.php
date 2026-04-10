<?php

namespace App\Services\Graph;

use RuntimeException;

class GraphManager
{
    protected static ?array $graph = null;

    public function __construct(protected MapData $mapData)
    {
        if (static::$graph === null) {
            $this->resetGraph();
        }
    }

    public function resetGraph(): void
    {
        static::$graph = [
            'nodes' => $this->mapData->getNodes(),
            'edges' => $this->mapData->getEdges(),
        ];
    }

    public function getGraph(): array
    {
        return static::$graph;
    }

    public function getAdjacencyGraph(): array
    {
        $adjacency = [];

        foreach (static::$graph['nodes'] as $node) {
            $adjacency[$node['id']] = [];
        }

        foreach (static::$graph['edges'] as $edge) {
            $adjacency[$edge['from']][] = [
                'id' => $edge['id'],
                'to' => $edge['to'],
                'cost' => $edge['current_weight'],
                'modes' => $edge['modes'],
                'base_weight' => $edge['base_weight'],
                'distance_km' => $edge['distance_km'],
                'car_allowed' => $edge['car_allowed'],
                'rickshaw_allowed' => $edge['rickshaw_allowed'],
                'walk_allowed' => $edge['walk_allowed'],
                'is_goli' => $edge['is_goli'],
                'is_overpass' => $edge['is_overpass'],
            ];
        }

        return $adjacency;
    }

    public function getNeighbours(string $nodeId, string $mode): array
    {
        $adjacency = $this->getAdjacencyGraph();

        if (! array_key_exists($nodeId, $adjacency)) {
            throw new RuntimeException("Unknown node [{$nodeId}].");
        }

        return array_values(array_filter(
            $adjacency[$nodeId],
            static fn (array $edge): bool => in_array($mode, $edge['modes'], true)
        ));
    }

    public function updateAnomalyZone(array $edgeIds, float|int $multiplier): array
    {
        return $this->updateAnomalyZoneWithBoundingBox($edgeIds, $multiplier, null);
    }

    public function updateAnomalyZoneWithBoundingBox(
        array $edgeIds,
        float|int $multiplier,
        ?array $boundingBox = null
    ): array {
        $affected = [];
        $nodeIndex = $this->getNodeIndex();
        $boundedEdgeIds = $boundingBox ? $this->findEdgesInBoundingBox($boundingBox, $nodeIndex) : [];
        $targetEdgeIds = array_values(array_unique(array_merge($edgeIds, $boundedEdgeIds)));

        foreach (static::$graph['edges'] as &$edge) {
            if (! in_array($edge['id'], $targetEdgeIds, true)) {
                continue;
            }

            $edge['current_weight'] = max(1, (int) round($edge['base_weight'] * $multiplier));
            $affected[] = $edge;
        }
        unset($edge);

        return $affected;
    }

    protected function getNodeIndex(): array
    {
        $nodeIndex = [];

        foreach (static::$graph['nodes'] as $node) {
            $nodeIndex[$node['id']] = $node;
        }

        return $nodeIndex;
    }

    protected function findEdgesInBoundingBox(array $boundingBox, array $nodeIndex): array
    {
        $minLat = $boundingBox['min_lat'] ?? null;
        $maxLat = $boundingBox['max_lat'] ?? null;
        $minLng = $boundingBox['min_lng'] ?? null;
        $maxLng = $boundingBox['max_lng'] ?? null;

        if ($minLat === null || $maxLat === null || $minLng === null || $maxLng === null) {
            return [];
        }

        $matchingEdgeIds = [];

        foreach (static::$graph['edges'] as $edge) {
            $fromNode = $nodeIndex[$edge['from']] ?? null;
            $toNode = $nodeIndex[$edge['to']] ?? null;

            if ($fromNode === null || $toNode === null) {
                continue;
            }

            $midLat = ($fromNode['lat'] + $toNode['lat']) / 2;
            $midLng = ($fromNode['lng'] + $toNode['lng']) / 2;

            if ($midLat < $minLat || $midLat > $maxLat || $midLng < $minLng || $midLng > $maxLng) {
                continue;
            }

            $matchingEdgeIds[] = $edge['id'];
        }

        return $matchingEdgeIds;
    }
}

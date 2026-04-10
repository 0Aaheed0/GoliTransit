<?php

namespace App\Services\Routing;

use App\Services\Graph\GraphManager;

class DemoGraphService
{
    public function __construct(protected GraphManager $graphManager)
    {
    }

    /**
     * Compatibility wrapper so the existing routing layer can consume
     * the graph manager's adjacency-list format.
     */
    public function getGraph(): array
    {
        return $this->graphManager->getAdjacencyGraph();
    }

    public function getNodes(): array
    {
        return array_column($this->graphManager->getGraph()['nodes'], 'id');
    }

    public function getEdges(): array
    {
        return $this->graphManager->getGraph()['edges'];
    }
}

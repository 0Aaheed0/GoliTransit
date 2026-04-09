# GoliTransit

GoliTransit is a hackathon backend for multi-modal routing in dense Dhaka-style traffic conditions. This repo uses Laravel on Vercel, but the team blueprint still maps cleanly:

- Member A: routing engine and `POST /api/route`
- Member B: graph data and graph manager contract
- Member C: anomaly flow, graph snapshot, error handling, and frontend/demo wiring

## Current status

Done now:

- project skeleton is ready
- `GET /health` exists
- `POST /api/route` works against a demo graph
- multi-modal routing with switch penalties is implemented
- graph edges now use stable `edge_id` values
- `GET /api/graph/snapshot` exists as a contract/debug endpoint
- session creation and reroute hooks exist for anomaly flow
- a repeatable route benchmark command exists for the 50-request step

Not done yet:

- real Dhaka graph data
- live anomaly updates to graph weights
- demo frontend visualization

## Team contract

These contracts are now frozen unless the whole team agrees to change them.

### Allowed modes

```json
["car", "rickshaw", "walk"]
```

### Node format

```json
{
  "id": "farmgate"
}
```

### Edge format

Every directed edge must use this shape:

```json
{
  "id": "edge_farmgate_karwan_bazar",
  "to": "karwan_bazar",
  "cost": 4,
  "modes": ["car", "rickshaw", "walk"]
}
```

### Graph format

Member B should build the real graph in this exact adjacency-list shape:

```json
{
  "farmgate": [
    {
      "id": "edge_farmgate_karwan_bazar",
      "to": "karwan_bazar",
      "cost": 4,
      "modes": ["car", "rickshaw", "walk"]
    }
  ],
  "karwan_bazar": []
}
```

## API contract

### `GET /health`

Response:

```json
{
  "status": "ok"
}
```

### `POST /api/route`

Purpose:
Return the best currently available route using one or more allowed travel modes.

Request body:

```json
{
  "session_id": "session-123",
  "start": "farmgate",
  "destination": "gulshan",
  "allowed_modes": ["car", "rickshaw", "walk"]
}
```

Current behavior:

- validation requires valid node IDs
- the engine searches across all provided modes
- mode switching is allowed only at configured transfer nodes
- each switch adds the configured switch penalty
- every route is saved to the in-memory session manager
- if `session_id` is omitted, the backend generates one automatically

Response shape:

```json
{
  "data": {
    "session_id": "session-123",
    "start": "farmgate",
    "destination": "gulshan",
    "allowed_modes": ["car", "rickshaw", "walk"],
    "selected_modes": ["walk", "rickshaw"],
    "path": ["farmgate", "green_road", "gulshan"],
    "nodes": ["farmgate", "green_road", "gulshan"],
    "segments": [
      {
        "edge_id": "edge_farmgate_karwan_bazar",
        "from": "farmgate",
        "to": "karwan_bazar",
        "cost": 4,
        "mode": "car",
        "previous_mode": "car",
        "switch_penalty": 0,
        "type": "travel"
      }
    ],
    "route_segments": [],
    "total_cost": 12,
    "switches": 1,
    "computation_time_ms": 2,
    "justification": {
      "summary": "Best available route on the current demo graph using the selected travel modes.",
      "mode_switches": 1,
      "mode_switch_penalty_applied": 3,
      "note": "This is the Step A3 multi-modal routing baseline with designated transfer nodes and switch penalties."
    },
    "session_saved": true
  }
}
```

### `GET /api/graph/snapshot`

Purpose:
Give Member B and Member C a stable debug endpoint that shows the current graph contract and edge IDs.

Response shape:

```json
{
  "data": {
    "nodes": ["farmgate", "karwan_bazar"],
    "edges": [
      {
        "id": "edge_farmgate_karwan_bazar",
        "from": "farmgate",
        "to": "karwan_bazar",
        "cost": 4,
        "modes": ["car", "rickshaw", "walk"]
      }
    ]
  }
}
```

### `POST /api/anomaly`

Purpose:
This is the future Step C3 endpoint. The request contract is frozen, and the session reroute hook is already wired so Member C can build on it.

Request body:

```json
{
  "edge_ids": ["edge_tejgaon_gulshan"],
  "multiplier": 10
}
```

Target behavior later:

- inflate the specified edge costs
- return affected edges, new weights, and rerouted-session count

Current behavior:

- validates the payload
- triggers `rerouteAffectedSessions(affectedEdges)` for any saved sessions already using those edge IDs
- returns `202 Accepted`
- does not yet change graph weights

## Ownership

### Member A

- routing logic
- route response contract
- future multi-modal switch logic
- future session rerouting logic

Files currently relevant:

- `app/Services/Routing/DemoGraphService.php`
- `app/Services/Routing/DijkstraRoutingService.php`
- `app/Services/Sessions/SessionManager.php`
- `app/Http/Controllers/Api/RouteController.php`
- `routes/api.php`

### Member B

- replace demo graph with real Dhaka graph
- keep node IDs and edge shape exactly as documented
- later add anomaly-aware graph update methods

Best starting point:

- `app/Services/Routing/DemoGraphService.php`

### Member C

- implement real anomaly handling behind `POST /api/anomaly`
- extend `GET /api/graph/snapshot`
- add error handling and demo-facing integration

Best starting points:

- `app/Http/Controllers/Api/AnomalyController.php`
- `app/Http/Controllers/Api/GraphSnapshotController.php`
- `app/Services/Sessions/SessionManager.php`

## Local commands

Install:

```bash
composer install
npm install
```

Serve locally:

```bash
php artisan serve
```

Useful checks:

```bash
php artisan route:list
php artisan golitransit:benchmark-route --base-url=http://127.0.0.1:8000
```

## Deployment notes

Vercel files already exist:

- `api/index.php`
- `vercel.json`

Deploy is not the current blocker. Finish the graph contract and feature slices first, then deploy once `GET /health` and the route flow are stable.

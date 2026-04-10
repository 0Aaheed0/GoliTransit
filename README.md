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

## Architecture

The project is split into three layers that mirror the hackathon team roles:

```text
+------------------------------+
| API Layer                    |
| Laravel routes/controllers   |
| /api/route /api/anomaly      |
| /api/graph/snapshot          |
+--------------+---------------+
               |
               v
+------------------------------+
| Routing Layer                |
| DijkstraRoutingService       |
| SessionManager               |
| mode switching + rerouting   |
+--------------+---------------+
               |
               v
+------------------------------+
| Graph Layer                  |
| MapData                      |
| GraphManager                 |
| Dhaka nodes, edges, weights  |
+------------------------------+
```

Layer ownership:

- Member B owns the graph layer
- Member A owns the routing layer
- Member C owns the API/anomaly/frontend integration layer

## Graph design decisions

Why 25-30 nodes:

- the hackathon asks for a simulated city network, so we chose 30 recognizable Dhaka locations
- this is large enough to demonstrate real rerouting behavior without making the demo graph too heavy to reason about live
- hubs such as Farmgate, Karwan Bazar, Mohakhali, Banani, Gulshan, Badda, Kuril, Shahbagh, and Motijheel were included so routes can branch in meaningful ways

How golis are modeled:

- goli segments are modeled as edges with `car_allowed=false`
- those same edges typically allow `rickshaw_allowed=true` and `walk_allowed=true`
- examples include `tejgaon_goli`, `banani_goli`, and the Old Dhaka to Sadarghat corridor
- this gives the routing layer a reason to prefer alleyway detours when motorized main roads degrade

How overpasses are modeled:

- overpasses are explicit nodes such as `farmgate_overpass` and `mohakhali_overpass`
- overpass edges are walk-only with `walk_allowed=true` and both motorized flags disabled
- this makes transfer behavior visible and supports the “walk across foot overpass” story from the problem statement

How transfer points are modeled:

- transfer logic lives in the routing config, but the graph was designed so hubs and overpasses naturally serve as switching opportunities
- Farmgate, Karwan Bazar, Green Road, and similar connection-heavy nodes are intended to be realistic transfer candidates

How anomaly inflation is modeled:

- each edge stores both `base_weight` and `current_weight`
- `updateAnomalyZone()` can inflate by explicit edge IDs
- `updateAnomalyZoneWithBoundingBox()` can also inflate all edges whose midpoint falls inside a lat/lng bounding box
- `getGraph()` immediately reflects the updated `current_weight` values so C's snapshot endpoint can prove the change

## Assumptions

- we assumed the simulated graph should use real Dhaka place names and approximate coordinates, not exact GIS-grade geometry
- we assumed VIP convoys and gridlock anomalies primarily affect weighted travel cost, not node existence
- we assumed golis are generally not car-accessible but remain viable for rickshaws and walking
- we assumed overpasses are walk-only transitions
- we assumed the graph is directional, so we modeled most roads with forward and reverse edges explicitly
- we assumed route switching should happen at a limited set of transfer nodes rather than anywhere in the graph
- we assumed a medium-sized graph is more useful for demo clarity than a street-perfect city export

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

Graph-layer support already available for Member B:

- `updateAnomalyZone(edgeIds, multiplier)`
- `updateAnomalyZoneWithBoundingBox(edgeIds, multiplier, boundingBox)`

Bounding box shape:

```json
{
  "min_lat": 23.75,
  "max_lat": 23.79,
  "min_lng": 90.39,
  "max_lng": 90.42
}
```

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

- `app/Services/Graph/MapData.php`
- `app/Services/Graph/GraphManager.php`

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

First-time local setup:

```bash
copy .env.example .env
php artisan key:generate --force
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

## Endpoints for judges

These are the 4 endpoints judges should be able to test immediately.

### 1. `GET /health`

Purpose:
Fast deployment and uptime check.

Example response:

```json
{
  "status": "ok"
}
```

### 2. `POST /api/route`

Purpose:
Return a best-effort route using the currently allowed modes and current edge weights.

Recommended example body:

```json
{
  "session_id": "demo-farmgate-gulshan",
  "start": "farmgate",
  "destination": "gulshan_2",
  "allowed_modes": ["car", "rickshaw", "walk"]
}
```

Example response shape:

```json
{
  "data": {
    "session_id": "demo-farmgate-gulshan",
    "start": "farmgate",
    "destination": "gulshan_2",
    "allowed_modes": ["car", "rickshaw", "walk"],
    "selected_modes": ["car"],
    "path": ["farmgate", "karwan_bazar", "tejgaon", "banani", "gulshan_1", "gulshan_2"],
    "nodes": ["farmgate", "karwan_bazar", "tejgaon", "banani", "gulshan_1", "gulshan_2"],
    "segments": [],
    "route_segments": [],
    "total_cost": 20,
    "switches": 0,
    "computation_time_ms": 4,
    "justification": {
      "summary": "Best available route on the current graph using the selected travel modes.",
      "mode_switches": 0,
      "mode_switch_penalty_applied": 0,
      "note": "Mode switching is allowed only at configured transfer nodes."
    },
    "session_saved": true
  }
}
```

### 3. `POST /api/anomaly`

Purpose:
Trigger anomaly handling against specific edge IDs now, and support bounding-box driven anomalies as the graph layer evolves.

Example body using edge IDs:

```json
{
  "edge_ids": ["edge_karwan_bazar_tejgaon", "edge_tejgaon_banani"],
  "multiplier": 10
}
```

Example body using an anomaly box at the graph layer:

```json
{
  "edge_ids": [],
  "multiplier": 10,
  "bounding_box": {
    "min_lat": 23.75,
    "max_lat": 23.79,
    "min_lng": 90.39,
    "max_lng": 90.42
  }
}
```

Current response shape:

```json
{
  "message": "Anomaly weight updates are still reserved for Step C3, but session rerouting is now wired.",
  "contract": {
    "edge_ids": ["edge_karwan_bazar_tejgaon"],
    "multiplier": 10
  },
  "reroute_summary": {
    "affected_edge_ids": ["edge_karwan_bazar_tejgaon"],
    "sessions_rerouted": 1,
    "sessions": []
  }
}
```

### 4. `GET /api/graph/snapshot`

Purpose:
Show the graph exactly as the system sees it right now, including `current_weight` after anomaly updates.

Example response shape:

```json
{
  "data": {
    "nodes": ["farmgate", "karwan_bazar", "tejgaon"],
    "edges": [
      {
        "id": "edge_karwan_bazar_tejgaon",
        "from": "karwan_bazar",
        "to": "tejgaon",
        "base_weight": 4,
        "current_weight": 4,
        "distance_km": 1.5,
        "car_allowed": true,
        "rickshaw_allowed": true,
        "walk_allowed": true
      }
    ]
  }
}

```

## Dhaka example points

These real Dhaka locations are already modeled in the graph and are good examples for demos and Postman requests:

- `farmgate` — 23.7580, 90.3892
- `karwan_bazar` — 23.7515, 90.3908
- `tejgaon` — 23.7637, 90.3973
- `mohakhali` — 23.7777, 90.4006
- `banani` — 23.7937, 90.4043
- `gulshan_1` — 23.7806, 90.4166
- `gulshan_2` — 23.7925, 90.4078
- `badda` — 23.7802, 90.4268
- `kuril` — 23.8205, 90.4218
- `motijheel` — 23.7313, 90.4175
- `old_dhaka` — 23.7118, 90.4074
- `sadarghat` — 23.7085, 90.4113

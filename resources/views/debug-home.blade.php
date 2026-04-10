<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>GoliTransit Control Room</title>
    <style>
        :root {
            --bg: #08100d;
            --surface: #101915;
            --surface-2: #15211c;
            --border: rgba(67, 201, 135, 0.16);
            --text: #ebf5ef;
            --muted: #98b0a3;
            --accent: #43c987;
            --accent-2: #8fffd1;
            --warning: #f6b84e;
            --danger: #ff7d7d;
            --line: rgba(255, 255, 255, 0.06);
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            color: var(--text);
            font-family: Arial, sans-serif;
            background:
                radial-gradient(circle at top left, rgba(67, 201, 135, 0.14), transparent 26%),
                radial-gradient(circle at top right, rgba(246, 184, 78, 0.10), transparent 20%),
                linear-gradient(180deg, #08100d 0%, #0c1411 100%);
            min-height: 100vh;
        }

        .wrap {
            max-width: 1280px;
            margin: 0 auto;
            padding: 28px 18px 48px;
        }

        .hero {
            display: grid;
            gap: 14px;
            margin-bottom: 22px;
        }

        .eyebrow {
            font-size: 12px;
            color: var(--accent);
            text-transform: uppercase;
            letter-spacing: 3px;
        }

        h1 {
            margin: 0;
            font-size: clamp(34px, 7vw, 66px);
            line-height: 0.94;
        }

        .subtitle {
            max-width: 820px;
            color: var(--muted);
            line-height: 1.7;
            font-size: 15px;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 12px;
            margin-bottom: 18px;
        }

        .card, .panel {
            border: 1px solid var(--border);
            border-radius: 16px;
            background: linear-gradient(180deg, rgba(21, 33, 28, 0.96), rgba(16, 25, 21, 0.98));
        }

        .card {
            padding: 18px;
        }

        .label {
            color: var(--muted);
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 10px;
        }

        .value {
            font-size: 34px;
            color: var(--accent-2);
            font-weight: 700;
        }

        .layout {
            display: grid;
            grid-template-columns: 380px 1fr;
            gap: 16px;
        }

        .panel {
            padding: 18px;
        }

        .panel h2 {
            margin: 0 0 14px;
            font-size: 20px;
        }

        .panel p {
            margin: 0 0 12px;
            color: var(--muted);
            line-height: 1.6;
            font-size: 14px;
        }

        .stack {
            display: grid;
            gap: 16px;
        }

        .field {
            display: grid;
            gap: 6px;
            margin-bottom: 12px;
        }

        .field label {
            color: var(--muted);
            font-size: 13px;
        }

        .field input, .field select, .field textarea {
            width: 100%;
            border-radius: 10px;
            border: 1px solid var(--line);
            background: rgba(255,255,255,0.03);
            color: var(--text);
            padding: 10px 12px;
            font: inherit;
        }

        .field textarea {
            min-height: 90px;
            resize: vertical;
        }

        .btn-row {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 8px;
        }

        button, .ghost-link {
            border: 1px solid var(--border);
            border-radius: 999px;
            background: rgba(67, 201, 135, 0.1);
            color: var(--accent-2);
            padding: 10px 14px;
            font: inherit;
            cursor: pointer;
            text-decoration: none;
        }

        button.warning {
            background: rgba(246, 184, 78, 0.10);
            color: #ffd08a;
        }

        .mono, pre {
            font-family: Consolas, monospace;
        }

        pre {
            margin: 0;
            white-space: pre-wrap;
            word-break: break-word;
            font-size: 12px;
            line-height: 1.65;
            color: #dce9e1;
        }

        .result {
            border-top: 1px solid var(--line);
            margin-top: 14px;
            padding-top: 14px;
        }

        .status {
            display: inline-flex;
            gap: 8px;
            align-items: center;
            border-radius: 999px;
            padding: 6px 12px;
            background: rgba(67, 201, 135, 0.08);
            color: var(--accent-2);
            font-size: 13px;
            margin-bottom: 10px;
        }

        .danger {
            color: var(--danger);
        }

        .map-wrap {
            border: 1px solid var(--line);
            border-radius: 14px;
            background:
                linear-gradient(var(--line) 1px, transparent 1px),
                linear-gradient(90deg, var(--line) 1px, transparent 1px),
                rgba(255,255,255,0.02);
            background-size: 36px 36px;
            min-height: 680px;
            overflow: hidden;
            position: relative;
        }

        svg {
            width: 100%;
            height: 680px;
            display: block;
        }

        .legend {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin: 14px 0 10px;
            color: var(--muted);
            font-size: 13px;
        }

        .legend span::before {
            content: '';
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-right: 8px;
        }

        .legend .normal::before { background: rgba(255,255,255,0.26); }
        .legend .route::before { background: var(--accent); }
        .legend .anomaly::before { background: var(--danger); }
        .legend .overpass::before { background: var(--warning); }

        .mini-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
            margin-top: 16px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }

        th, td {
            padding: 8px 6px;
            border-bottom: 1px solid var(--line);
            text-align: left;
            vertical-align: top;
        }

        th {
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 1px;
            font-size: 11px;
        }

        @media (max-width: 980px) {
            .layout {
                grid-template-columns: 1fr;
            }

            .map-wrap, svg {
                min-height: 520px;
                height: 520px;
            }

            .mini-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="hero">
            <div class="eyebrow">GoliTransit Control Room</div>
            <h1>Route, Anomaly, Snapshot, Visual Demo</h1>
            <div class="subtitle">
                This page is the fast local frontend for the hackathon demo. It lets you compute routes, trigger anomalies, inspect graph weights, and visually see the network without needing the broken default Laravel starter UI.
            </div>
        </div>

        <div class="stats">
            <div class="card"><div class="label">Nodes</div><div class="value">{{ $nodeCount }}</div></div>
            <div class="card"><div class="label">Edges</div><div class="value">{{ $edgeCount }}</div></div>
            <div class="card"><div class="label">Goli Edges</div><div class="value">{{ $goliEdgeCount }}</div></div>
            <div class="card"><div class="label">Overpass Nodes</div><div class="value">{{ $overpassNodeCount }}</div></div>
        </div>

        <div class="layout">
            <div class="stack">
                <div class="panel">
                    <h2>Route Request</h2>
                    <p>Compute a route and save a session automatically for later rerouting.</p>

                    <div class="field">
                        <label for="start">Start</label>
                        <select id="start"></select>
                    </div>
                    <div class="field">
                        <label for="destination">Destination</label>
                        <select id="destination"></select>
                    </div>
                    <div class="field">
                        <label for="sessionId">Session ID</label>
                        <input id="sessionId" value="demo-local-session">
                    </div>
                    <div class="field">
                        <label for="modes">Allowed modes</label>
                        <select id="modes" multiple size="3">
                            <option value="car" selected>car</option>
                            <option value="rickshaw" selected>rickshaw</option>
                            <option value="walk" selected>walk</option>
                        </select>
                    </div>
                    <div class="btn-row">
                        <button id="routeBtn" type="button">Run Route</button>
                    </div>
                    <div class="result">
                        <div class="status">Sample Route Status</div>
                        <pre id="routeResult">{{ $sampleRouteError ? $sampleRouteError : json_encode($sampleRoute, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                    </div>
                </div>

                <div class="panel">
                    <h2>Anomaly Trigger</h2>
                    <p>Inflate graph weights by edge IDs or by a lat/lng bounding box, then reroute impacted sessions.</p>

                    <div class="field">
                        <label for="edgeIds">Edge IDs (comma separated)</label>
                        <input id="edgeIds" value="edge_karwan_bazar_tejgaon,edge_tejgaon_banani">
                    </div>
                    <div class="field">
                        <label for="multiplier">Multiplier</label>
                        <input id="multiplier" type="number" min="1" value="10">
                    </div>
                    <div class="field">
                        <label for="boundingBox">Bounding box JSON (optional)</label>
                        <textarea id="boundingBox">{
  "min_lat": 23.75,
  "max_lat": 23.79,
  "min_lng": 90.39,
  "max_lng": 90.42
}</textarea>
                    </div>
                    <div class="btn-row">
                        <button class="warning" id="anomalyBtn" type="button">Trigger Anomaly</button>
                        <button id="snapshotBtn" type="button">Refresh Snapshot</button>
                        <a class="ghost-link" href="/api/graph/snapshot" target="_blank">Open JSON Snapshot</a>
                    </div>
                    <div class="result">
                        <div class="status">Anomaly Result</div>
                        <pre id="anomalyResult">No anomaly triggered yet.</pre>
                    </div>
                </div>

                <div class="panel">
                    <h2>Quick Checks</h2>
                    <div class="btn-row">
                        <a class="ghost-link" href="/health" target="_blank">GET /health</a>
                        <a class="ghost-link" href="/api/graph/snapshot" target="_blank">GET /api/graph/snapshot</a>
                    </div>
                    <div class="mini-grid">
                        <div>
                            <div class="label">Sample Nodes</div>
                            <table id="nodeTable"></table>
                        </div>
                        <div>
                            <div class="label">Highlighted Edges</div>
                            <table id="edgeTable"></table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="panel">
                <h2>Visual Map</h2>
                <p>Grey lines are normal roads, green is the current route, red are anomaly-affected edges, and amber nodes are overpasses.</p>
                <div class="legend">
                    <span class="normal">Normal edges</span>
                    <span class="route">Current route</span>
                    <span class="anomaly">Anomaly edges</span>
                    <span class="overpass">Overpass nodes</span>
                </div>
                <div class="map-wrap">
                    <svg id="graphSvg" viewBox="0 0 900 680" preserveAspectRatio="xMidYMid meet"></svg>
                </div>
            </div>
        </div>
    </div>

    <script id="graph-data" type="application/json">@json(['nodes' => $nodes, 'edges' => $edges])</script>
    <script id="sample-route" type="application/json">@json($sampleRoute)</script>
    <script>
        const graphData = JSON.parse(document.getElementById('graph-data').textContent);
        let currentGraph = graphData;
        let currentRoute = JSON.parse(document.getElementById('sample-route').textContent);
        let anomalyEdgeIds = [];

        const svg = document.getElementById('graphSvg');
        const routeResult = document.getElementById('routeResult');
        const anomalyResult = document.getElementById('anomalyResult');
        const nodeTable = document.getElementById('nodeTable');
        const edgeTable = document.getElementById('edgeTable');
        const startSelect = document.getElementById('start');
        const destinationSelect = document.getElementById('destination');

        function populateSelects() {
            const options = currentGraph.nodes.map((node) => `<option value="${node.id}">${node.name} (${node.id})</option>`).join('');
            startSelect.innerHTML = options;
            destinationSelect.innerHTML = options;
            startSelect.value = 'farmgate';
            destinationSelect.value = 'gulshan_2';
        }

        function projection(nodes) {
            const lats = nodes.map((n) => n.lat);
            const lngs = nodes.map((n) => n.lng);
            const minLat = Math.min(...lats);
            const maxLat = Math.max(...lats);
            const minLng = Math.min(...lngs);
            const maxLng = Math.max(...lngs);

            return Object.fromEntries(nodes.map((node) => {
                const x = 60 + ((node.lng - minLng) / (maxLng - minLng || 1)) * 780;
                const y = 620 - ((node.lat - minLat) / (maxLat - minLat || 1)) * 560;
                return [node.id, { x, y, node }];
            }));
        }

        function routeEdgeIds(route) {
            if (!route || !Array.isArray(route.segments)) {
                return [];
            }

            return route.segments
                .filter((segment) => segment.edge_id)
                .map((segment) => segment.edge_id);
        }

        function draw() {
            const points = projection(currentGraph.nodes);
            const activeRouteEdges = new Set(routeEdgeIds(currentRoute));

            const edgesMarkup = currentGraph.edges.map((edge) => {
                const from = points[edge.from];
                const to = points[edge.to];
                if (!from || !to) return '';

                let stroke = 'rgba(255,255,255,0.18)';
                let width = 1.5;

                if (anomalyEdgeIds.includes(edge.id)) {
                    stroke = '#ff7d7d';
                    width = 3.2;
                } else if (activeRouteEdges.has(edge.id)) {
                    stroke = '#43c987';
                    width = 3.4;
                } else if (edge.is_goli) {
                    stroke = 'rgba(143,255,209,0.42)';
                    width = 2;
                }

                return `<g>
                    <line x1="${from.x}" y1="${from.y}" x2="${to.x}" y2="${to.y}" stroke="${stroke}" stroke-width="${width}" stroke-linecap="round" />
                </g>`;
            }).join('');

            const nodesMarkup = currentGraph.nodes.map((node) => {
                const point = points[node.id];
                const fill = node.type === 'overpass' ? '#f6b84e' : (node.type === 'goli' ? '#8fffd1' : '#ebf5ef');
                const radius = node.type === 'hub' ? 6 : 4.5;
                return `<g>
                    <circle cx="${point.x}" cy="${point.y}" r="${radius}" fill="${fill}" stroke="#08100d" stroke-width="2"></circle>
                    <text x="${point.x + 8}" y="${point.y - 8}" fill="#dce9e1" font-size="11">${node.name}</text>
                </g>`;
            }).join('');

            svg.innerHTML = `${edgesMarkup}${nodesMarkup}`;
            renderTables();
        }

        function renderTables() {
            nodeTable.innerHTML = `
                <thead><tr><th>Name</th><th>Type</th></tr></thead>
                <tbody>
                    ${currentGraph.nodes.slice(0, 8).map((node) => `<tr><td>${node.name}</td><td>${node.type}</td></tr>`).join('')}
                </tbody>
            `;

            const highlightedEdges = currentGraph.edges.filter((edge) => anomalyEdgeIds.includes(edge.id) || routeEdgeIds(currentRoute).includes(edge.id)).slice(0, 8);
            edgeTable.innerHTML = `
                <thead><tr><th>ID</th><th>Weight</th></tr></thead>
                <tbody>
                    ${highlightedEdges.map((edge) => `<tr><td>${edge.id}</td><td>${edge.current_weight}</td></tr>`).join('')}
                </tbody>
            `;
        }

        async function refreshSnapshot() {
            const response = await fetch('/api/graph/snapshot');
            const snapshot = await response.json();
            currentGraph = snapshot.data;
            draw();
            return snapshot;
        }

        async function runRoute() {
            const selectedModes = Array.from(document.getElementById('modes').selectedOptions).map((option) => option.value);
            const payload = {
                session_id: document.getElementById('sessionId').value || undefined,
                start: startSelect.value,
                destination: destinationSelect.value,
                allowed_modes: selectedModes
            };

            const response = await fetch('/api/route', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify(payload)
            });

            const result = await response.json();
            routeResult.textContent = JSON.stringify(result, null, 2);

            if (response.ok) {
                currentRoute = result.data;
                draw();
            }
        }

        async function runAnomaly() {
            let boundingBox = null;
            const rawBox = document.getElementById('boundingBox').value.trim();
            if (rawBox) {
                try {
                    boundingBox = JSON.parse(rawBox);
                } catch (error) {
                    anomalyResult.textContent = 'Bounding box JSON is invalid.';
                    return;
                }
            }

            const edgeIds = document.getElementById('edgeIds').value
                .split(',')
                .map((item) => item.trim())
                .filter(Boolean);

            const payload = {
                edge_ids: edgeIds,
                multiplier: Number(document.getElementById('multiplier').value),
            };

            if (boundingBox) {
                payload.bounding_box = boundingBox;
            }

            const response = await fetch('/api/anomaly', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify(payload)
            });

            const result = await response.json();
            anomalyResult.textContent = JSON.stringify(result, null, 2);

            if (response.ok) {
                anomalyEdgeIds = result.reroute_summary?.affected_edge_ids || [];
                await refreshSnapshot();
            }
        }

        document.getElementById('routeBtn').addEventListener('click', runRoute);
        document.getElementById('anomalyBtn').addEventListener('click', runAnomaly);
        document.getElementById('snapshotBtn').addEventListener('click', refreshSnapshot);

        populateSelects();
        draw();
    </script>
</body>
</html>

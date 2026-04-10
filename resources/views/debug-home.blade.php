<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>GoliTransit Debug</title>
    <style>
        :root {
            --bg: #08110d;
            --panel: #101a15;
            --panel-2: #13211b;
            --border: rgba(67, 201, 135, 0.18);
            --text: #eaf4ee;
            --muted: #9db4a7;
            --accent: #43c987;
            --accent-2: #8fffd1;
            --danger: #ff8c8c;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background:
                radial-gradient(circle at top left, rgba(67, 201, 135, 0.14), transparent 28%),
                linear-gradient(180deg, #08110d 0%, #0b1410 100%);
            color: var(--text);
            min-height: 100vh;
        }

        .wrap {
            max-width: 1180px;
            margin: 0 auto;
            padding: 32px 20px 56px;
        }

        .hero {
            display: grid;
            gap: 18px;
            margin-bottom: 26px;
        }

        .eyebrow {
            font-size: 12px;
            letter-spacing: 3px;
            text-transform: uppercase;
            color: var(--accent);
        }

        h1 {
            margin: 0;
            font-size: clamp(34px, 7vw, 62px);
            line-height: 0.95;
        }

        .subtitle {
            max-width: 780px;
            color: var(--muted);
            line-height: 1.7;
            font-size: 15px;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 14px;
            margin-bottom: 22px;
        }

        .card,
        .panel {
            background: linear-gradient(180deg, rgba(19, 33, 27, 0.95), rgba(16, 26, 21, 0.95));
            border: 1px solid var(--border);
            border-radius: 16px;
        }

        .card {
            padding: 18px;
        }

        .card-label {
            color: var(--muted);
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 12px;
        }

        .card-value {
            font-size: 34px;
            font-weight: 700;
            color: var(--accent-2);
        }

        .section-grid {
            display: grid;
            grid-template-columns: 1.2fr 1fr;
            gap: 18px;
        }

        .panel {
            padding: 20px;
        }

        .panel h2 {
            margin: 0 0 14px;
            font-size: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        th, td {
            text-align: left;
            padding: 10px 8px;
            border-bottom: 1px solid rgba(67, 201, 135, 0.12);
            vertical-align: top;
        }

        th {
            color: var(--muted);
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .pill-row {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
        }

        .pill {
            border: 1px solid var(--border);
            border-radius: 999px;
            padding: 6px 10px;
            color: var(--accent-2);
            background: rgba(67, 201, 135, 0.08);
            font-size: 12px;
        }

        pre {
            white-space: pre-wrap;
            word-break: break-word;
            margin: 0;
            font-size: 13px;
            line-height: 1.6;
            color: #dbeae1;
        }

        .error {
            color: var(--danger);
            font-weight: 700;
        }

        .footer-links {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 18px;
        }

        .footer-links a {
            color: var(--accent-2);
            text-decoration: none;
            border: 1px solid var(--border);
            border-radius: 999px;
            padding: 8px 12px;
            background: rgba(67, 201, 135, 0.08);
        }

        @media (max-width: 900px) {
            .section-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="hero">
            <div class="eyebrow">GoliTransit Local Debug</div>
            <h1>Current Project Snapshot</h1>
            <div class="subtitle">
                This is a temporary visual status page for your hackathon backend. It shows whether the graph is loaded, whether the routing engine can produce a route, and gives you a quick way to inspect the data without digging through raw JSON.
            </div>
        </div>

        <div class="grid">
            <div class="card">
                <div class="card-label">Nodes</div>
                <div class="card-value">{{ $nodeCount }}</div>
            </div>
            <div class="card">
                <div class="card-label">Edges</div>
                <div class="card-value">{{ $edgeCount }}</div>
            </div>
            <div class="card">
                <div class="card-label">Goli Edges</div>
                <div class="card-value">{{ $goliEdgeCount }}</div>
            </div>
            <div class="card">
                <div class="card-label">Overpass Nodes</div>
                <div class="card-value">{{ $overpassNodeCount }}</div>
            </div>
        </div>

        <div class="section-grid">
            <div class="panel">
                <h2>Sample Route: Farmgate to Gulshan 2</h2>

                @if ($sampleRouteError)
                    <div class="error">{{ $sampleRouteError }}</div>
                @elseif ($sampleRoute)
                    <div class="pill-row">
                        <div class="pill">Total Cost: {{ $sampleRoute['total_cost'] }}</div>
                        <div class="pill">Switches: {{ $sampleRoute['mode_switches'] }}</div>
                        <div class="pill">Penalty: {{ $sampleRoute['mode_switch_penalty_applied'] }}</div>
                        @foreach ($sampleRoute['selected_modes'] as $mode)
                            <div class="pill">Mode: {{ $mode }}</div>
                        @endforeach
                    </div>

                    <pre>{{ json_encode($sampleRoute, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                @endif
            </div>

            <div class="panel">
                <h2>Useful Links</h2>
                <div class="footer-links">
                    <a href="/health" target="_blank">GET /health</a>
                    <a href="/api/graph/snapshot" target="_blank">GET /api/graph/snapshot</a>
                </div>

                <h2 style="margin-top: 22px;">Try Route API</h2>
                <pre>{
  "session_id": "debug-home-route",
  "start": "farmgate",
  "destination": "gulshan_2",
  "allowed_modes": ["car", "rickshaw", "walk"]
}</pre>
            </div>
        </div>

        <div class="section-grid" style="margin-top: 18px;">
            <div class="panel">
                <h2>Sample Nodes</h2>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Lat</th>
                            <th>Lng</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($nodes as $node)
                            <tr>
                                <td>{{ $node['id'] }}</td>
                                <td>{{ $node['name'] }}</td>
                                <td>{{ $node['type'] }}</td>
                                <td>{{ $node['lat'] }}</td>
                                <td>{{ $node['lng'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="panel">
                <h2>Sample Edges</h2>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>From</th>
                            <th>To</th>
                            <th>Weight</th>
                            <th>Modes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($edges as $edge)
                            <tr>
                                <td>{{ $edge['id'] }}</td>
                                <td>{{ $edge['from'] }}</td>
                                <td>{{ $edge['to'] }}</td>
                                <td>{{ $edge['current_weight'] }}</td>
                                <td>{{ implode(', ', $edge['modes']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>

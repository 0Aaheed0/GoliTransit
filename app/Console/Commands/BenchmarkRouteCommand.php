<?php

namespace App\Console\Commands;

use GuzzleHttp\Client;
use GuzzleHttp\Pool;
use Illuminate\Console\Command;

class BenchmarkRouteCommand extends Command
{
    protected $signature = 'golitransit:benchmark-route
                            {--base-url=http://127.0.0.1:8000 : Base URL to test}
                            {--requests=50 : Total number of route requests to send}
                            {--concurrency=50 : Number of concurrent workers}
                            {--max-p95-ms=2500 : Target p95 latency threshold in ms}';

    protected $description = 'Benchmark POST /api/route and check the unused-edge anomaly case.';

    public function handle(): int
    {
        $baseUrl = rtrim((string) $this->option('base-url'), '/');
        $requestCount = max(1, (int) $this->option('requests'));
        $concurrency = max(1, (int) $this->option('concurrency'));
        $maxP95 = max(1, (int) $this->option('max-p95-ms'));

        $client = new Client([
            'base_uri' => $baseUrl,
            'http_errors' => false,
            'timeout' => 10,
        ]);

        $this->info("Benchmarking {$requestCount} POST /api/route requests against {$baseUrl}");

        $latencies = [];
        $statusCounts = [];
        $failures = [];

        $requests = function () use ($client, $requestCount, &$latencies) {
            for ($i = 0; $i < $requestCount; $i++) {
                $payload = [
                    'session_id' => "bench-{$i}",
                    'start' => 'farmgate',
                    'destination' => 'gulshan',
                    'allowed_modes' => ['car', 'rickshaw', 'walk'],
                ];

                yield function () use ($client, $payload, $i, &$latencies) {
                    $start = microtime(true);

                    return $client->postAsync('/api/route', [
                        'json' => $payload,
                    ])->then(function ($response) use (&$latencies, $start, $i) {
                        $latencies[$i] = (microtime(true) - $start) * 1000;

                        return $response;
                    });
                };
            }
        };

        $pool = new Pool($client, $requests(), [
            'concurrency' => $concurrency,
            'fulfilled' => function ($response) use (&$statusCounts, &$failures) {
                $status = $response->getStatusCode();
                $statusCounts[$status] = ($statusCounts[$status] ?? 0) + 1;

                if ($status !== 200) {
                    $failures[] = [
                        'status' => $status,
                        'body' => (string) $response->getBody(),
                    ];
                }
            },
            'rejected' => function ($reason) use (&$statusCounts, &$failures) {
                $statusCounts['rejected'] = ($statusCounts['rejected'] ?? 0) + 1;
                $failures[] = [
                    'status' => 'rejected',
                    'body' => (string) $reason,
                ];
            },
        ]);

        $pool->promise()->wait();

        if ($latencies === []) {
            $this->error('No latency samples were collected. Check that the app is running and reachable.');

            return self::FAILURE;
        }

        sort($latencies);

        $p95Index = (int) ceil(count($latencies) * 0.95) - 1;
        $p95 = $latencies[max(0, $p95Index)];
        $avg = array_sum($latencies) / count($latencies);
        $max = max($latencies);

        $this->table(
            ['Metric', 'Value'],
            [
                ['requests', (string) $requestCount],
                ['concurrency', (string) $concurrency],
                ['avg_ms', number_format($avg, 2)],
                ['p95_ms', number_format($p95, 2)],
                ['max_ms', number_format($max, 2)],
                ['status_counts', json_encode($statusCounts, JSON_UNESCAPED_SLASHES)],
            ]
        );

        $unusedEdgeResponse = $client->post('/api/anomaly', [
            'json' => [
                'edge_ids' => ['edge_unused_for_benchmark'],
                'multiplier' => 10,
            ],
        ]);

        $unusedEdgeBody = json_decode((string) $unusedEdgeResponse->getBody(), true);
        $unusedEdgeReroutes = $unusedEdgeBody['reroute_summary']['sessions_rerouted'] ?? null;

        $this->table(
            ['Edge Case', 'Result'],
            [
                ['unused_edge_status', (string) $unusedEdgeResponse->getStatusCode()],
                ['unused_edge_reroutes', (string) $unusedEdgeReroutes],
            ]
        );

        if ($failures !== []) {
            $this->warn('Some requests failed during benchmark.');
            $this->line(json_encode(array_slice($failures, 0, 3), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        }

        if ($p95 > $maxP95) {
            $this->warn("p95 latency exceeded target ({$maxP95} ms).");

            return self::FAILURE;
        }

        if ($unusedEdgeResponse->getStatusCode() >= 500) {
            $this->warn('Unused-edge anomaly check failed.');

            return self::FAILURE;
        }

        $this->info('Benchmark completed successfully.');

        return self::SUCCESS;
    }
}

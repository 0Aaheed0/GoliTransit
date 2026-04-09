<?php

return [
    'mode_switch_penalty' => env('GOLITRANSIT_MODE_SWITCH_PENALTY', 3),

    // Only these nodes are allowed to switch transport modes in the A3 baseline.
    'transfer_nodes' => [
        'farmgate',
        'karwan_bazar',
        'green_road',
    ],
];

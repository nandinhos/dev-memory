<?php

return [
    'rrf_k' => 60,

    'rerank' => [
        'enabled' => env('SEARCH_RERANK_ENABLED', true),

        'weights' => [
            'validation_status' => [
                'validated' => 1.30,
                'pending' => 1.00,
                'rejected' => 0.50,
                'superseded' => 0.30,
            ],
            'recurrence_factor' => 0.15,
            'doc_validation' => [
                'confirmed' => 1.25,
                'partially_confirmed' => 1.10,
                'pending' => 1.00,
                'inconclusive' => 1.00,
                'unverified' => 1.00,
                'contradicted' => 0.40,
            ],
            'stack_boost' => 0.20,
            'maturity' => [
                'consolidated' => 1.20,
                'canonical' => 1.15,
                'recommended' => 1.10,
                'provisional' => 1.00,
                'workaround' => 0.90,
            ],
            'recency' => [
                'fresh_days' => 60,
                'fresh_boost' => 1.05,
                'stale_days' => 180,
                'stale_penalty' => 0.95,
            ],
        ],
    ],
];

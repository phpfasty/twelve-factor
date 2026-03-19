<?php

declare(strict_types=1);

return [
    'default' => [
        'max_requests' => 3,
        'time_window_seconds' => 10,
        'block_seconds' => 10,
    ],

    // Route scan (all requested URLs from one IP). Keep generous for normal navigation.
    'scan' => [
        'enabled' => true,
        'max_requests' => 20,
        'time_window_seconds' => 10,
        'block_seconds' => 20,
    ],

    // Additional restriction for requests that look automated (missing UA, known bot UA, etc.).
    'suspicious' => [
        'enabled' => true,
        'max_requests' => 2,
        'time_window_seconds' => 10,
        'block_seconds' => 30,
    ],

    // Request header heuristics for suspicious request detection.
    'headers' => [
        'enabled' => true,
        'require_user_agent' => true,
        'min_user_agent_length' => 12,
        'require_accept_language' => false,
        'blocked_user_agents' => [
            'curl',
            'wget',
            'python-requests',
            'go-http-client',
            'java/',
            'libwww',
        ],
    ],

    // Goodbye page: time window for counting visits per IP (image vs video).
    'goodbye' => [
        'visits_window_seconds' => 3600, // 1 hour, 86400 (24 hours)
    ],

    'methods' => [
        // More strict for POST endpoints such as contact forms.
        'POST' => [
            'max_requests' => 4,
            'time_window_seconds' => 30,
            'block_seconds' => 30,
        ],
    ],

    'routes' => [
        'exact' => [
            '/api/landing' => [
                'max_requests' => 5,
                'time_window_seconds' => 10,
                'block_seconds' => 20,
            ],
            '/api/health' => [
                'max_requests' => 5,
                'time_window_seconds' => 10,
                'block_seconds' => 20,
            ],
        ],
        'prefix' => [
            '/api/' => [
                'max_requests' => 5,
                'time_window_seconds' => 20,
                'block_seconds' => 30,
            ],
        ],
    ],
];


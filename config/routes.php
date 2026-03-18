<?php

declare(strict_types=1);


Flight::route('GET /api/health', static function (): void {
    Flight::json([
        'status' => 'ok',
    ]);
});

Flight::route('GET /api/landing', static function (): void {
    Flight::json([
        'message' => 'landing placeholder',
        'status' => 'not_implemented',
    ]);
});

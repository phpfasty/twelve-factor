<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Application;
use Dotenv\Dotenv;

$dotenvPath = dirname(__DIR__);
if (is_readable($dotenvPath . '/.env')) {
    Dotenv::createImmutable($dotenvPath)->load();
}

Application::create();

if (class_exists(Application::class)) {
    $container = Application::getInstance()->getContainer();
    Flight::set('appContainer', $container);
}

$debug = filter_var(
    getenv('APP_DEBUG') ?: 'false',
    FILTER_VALIDATE_BOOLEAN,
    FILTER_NULL_ON_FAILURE
);
$debug = $debug === null ? false : $debug;

Flight::set('flight.base_url', '/');
Flight::set('flight.log_errors', $debug);

require_once __DIR__ . '/../config/routes.php';

Flight::start();

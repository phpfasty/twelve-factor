<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Cache\CacheStore;
use App\Core\Application;
use App\Service\PageRenderer;
use Dotenv\Dotenv;

$rootDir = dirname(__DIR__);

if (is_readable($rootDir . '/.env')) {
    Dotenv::createImmutable($rootDir)->load();
}

$application = Application::getInstance();
$container = $application->getContainer();

/** @var CacheStore $cacheStore */
$cacheStore = $container->get(CacheStore::class);
/** @var PageRenderer $pageRenderer */
$pageRenderer = $container->get(PageRenderer::class);
/** @var array<string, array<string, mixed>> $pages */
$pages = $container->get('pages_config');

$cacheStore->flush();

$warmedPaths = [];

foreach ($pages as $routePath => $pageConfig) {
    foreach ($pageRenderer->getRouteParameterSets($pageConfig) as $routeParameters) {
        $pageRenderer->renderPage($routePath, $pageConfig, $routeParameters, true);
        $warmedPaths[] = $pageRenderer->buildRequestPath($routePath, $routeParameters);
    }
}

echo 'Page cache warmed for ' . count($warmedPaths) . ' route(s).' . PHP_EOL;

foreach ($warmedPaths as $path) {
    echo ' - ' . $path . PHP_EOL;
}

<?php

declare(strict_types=1);

use App\Core\ContainerInterface;
use App\Data\DataProviderInterface;
use App\Middleware\SecurityHeaders;
use App\Service\PageRenderer;

$container = Flight::get('appContainer');
if (!$container instanceof ContainerInterface) {
    throw new RuntimeException('Application container is not available.');
}

/** @var SecurityHeaders $securityHeaders */
$securityHeaders = $container->get(SecurityHeaders::class);
/** @var DataProviderInterface $dataProvider */
$dataProvider = $container->get(DataProviderInterface::class);
/** @var PageRenderer $pageRenderer */
$pageRenderer = $container->get(PageRenderer::class);
/** @var array<string, array<string, mixed>> $pages */
$pages = $container->get('pages_config');

$extractRouteParameters = static function (string $routePath, array $arguments): array {
    $parameterNames = [];
    if (preg_match_all('/@([a-zA-Z_][a-zA-Z0-9_]*)/', $routePath, $matches) === 1) {
        $parameterNames = $matches[1];
    }

    $parameters = [];

    foreach ($parameterNames as $index => $parameterName) {
        $argument = $arguments[$index] ?? '';
        $parameters[$parameterName] = is_scalar($argument) ? (string) $argument : '';
    }

    return $parameters;
};

Flight::route('GET /api/health', static function () use ($securityHeaders): void {
    $securityHeaders->applyApiHeaders();

    Flight::json([
        'status' => 'ok',
    ]);
});

Flight::route('GET /api/landing', static function () use ($dataProvider, $securityHeaders): void {
    $securityHeaders->applyApiHeaders();
    Flight::json($dataProvider->get('landing'));
});

foreach ($pages as $routePath => $pageConfig) {
    Flight::route('GET ' . $routePath, static function (...$arguments) use (
        $extractRouteParameters,
        $pageConfig,
        $pageRenderer,
        $routePath,
        $securityHeaders
    ): void {
        $routeParameters = $extractRouteParameters($routePath, $arguments);

        try {
            $html = $pageRenderer->renderPage($routePath, $pageConfig, $routeParameters);
        } catch (RuntimeException) {
            Flight::notFound();

            return;
        }

        $securityHeaders->applyStaticHeaders();
        Flight::response()->header('Content-Type', 'text/html; charset=UTF-8');
        echo $html;
    });
}

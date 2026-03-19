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

foreach (['en', 'ru'] as $locale) {
    $pageRenderer->setLocale($locale);
    $nextLocale = $locale === 'ru' ? 'en' : 'ru';
    foreach ($pages as $routePath => $pageConfig) {
        foreach ($pageRenderer->getRouteParameterSets($pageConfig) as $routeParameters) {
            $path = $pageRenderer->buildRequestPath($routePath, $routeParameters);
            $langSwitchUrl = $path . '?lang=' . $nextLocale;
            $extraData = [
                'lang_switch_url' => $langSwitchUrl,
                'lang_toggle_label' => $locale === 'ru' ? 'Ru' : 'En',
            ];
            if (($pageConfig['hide_layout'] ?? false) && str_contains($path, 'goodbye')) {
                $extraData['show_video'] = false;
                $extraData['cache_key_suffix'] = ':video=0';
            }
            $pageRenderer->renderPage($routePath, $pageConfig, $routeParameters, true, $locale, $extraData);
            $warmedPaths[] = $path . ' [' . $locale . ']';
        }
    }
}

echo 'Page cache warmed for ' . count($warmedPaths) . ' route(s).' . PHP_EOL;

foreach ($warmedPaths as $path) {
    echo ' - ' . $path . PHP_EOL;
}

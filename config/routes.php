<?php

declare(strict_types=1);

use App\Defense\RequestDefenseService;
use App\Localization\SiteLocaleManager;
use PhpFasty\Core\ContainerInterface;
use PhpFasty\Core\Data\DataProviderInterface;
use PhpFasty\Core\Middleware\SecurityHeaders;
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
/** @var RequestDefenseService $defenseService */
$defenseService = $container->get(RequestDefenseService::class);
/** @var SiteLocaleManager $localeManager */
$localeManager = $container->get(SiteLocaleManager::class);
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

$resolveLocale = static function () use ($localeManager): string {
    $resolvedLocale = $localeManager->resolveRequestLocale(
        (string) ($_GET['lang'] ?? ''),
        (string) ($_COOKIE['mk-lang'] ?? ''),
        (string) ($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '')
    );

    setcookie('mk-lang', $resolvedLocale, [
        'expires' => time() + 60 * 60 * 24 * 365,
        'path' => '/',
        'samesite' => 'Lax',
    ]);

    return $resolvedLocale;
};

$buildLanguageSwitchUrl = static function (string $locale): string {
    $requestUri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
    $path = (string) (parse_url($requestUri, PHP_URL_PATH) ?: '/');
    if ($path === '') {
        $path = '/';
    }

    $query = $_GET;
    unset($query['lang']);
    $query['lang'] = $locale;

    return $path . '?' . http_build_query($query);
};

$resolveRequestPath = static function (): string {
    $requestUri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
    $path = (string) (parse_url($requestUri, PHP_URL_PATH) ?: '/');

    if ($path === '') {
        return '/';
    }

    if ($path !== '/' && str_ends_with($path, '/')) {
        return rtrim($path, '/');
    }

    return $path;
};

$resolveRequestMethod = static function (): string {
    $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
    return $method === '' ? 'GET' : $method;
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
        $securityHeaders,
        $defenseService,
        $localeManager,
        $resolveLocale,
        $buildLanguageSwitchUrl,
        $resolveRequestPath,
        $resolveRequestMethod
    ): void {
        $routeParameters = $extractRouteParameters($routePath, $arguments);
        $requestPath = $resolveRequestPath();
        $requestMethod = $resolveRequestMethod();
        if ($requestPath !== '/goodbye') {
            $clientIp = $defenseService->getClientIp($_SERVER);
            if ($defenseService->isLimitExceeded($clientIp, $requestPath, $requestMethod, $_SERVER)) {
                Flight::redirect('/goodbye');

                return;
            }
        }
        $locale = $resolveLocale();
        $nextLocale = $localeManager->getNextLocale($locale);
        $pageRenderer->setLocale($locale);
        $languageSwitchUrl = $buildLanguageSwitchUrl($nextLocale);
        $extraStylesheets = is_array($pageConfig['stylesheets'] ?? null) ? $pageConfig['stylesheets'] : [];
        $extraData = [
            'lang_switch_url' => $languageSwitchUrl,
            'lang_toggle_label' => $localeManager->getLabel($nextLocale),
            'html_lang' => $localeManager->getHtmlLang($locale),
            'og_locale' => $localeManager->getOpenGraphLocale($locale),
            'extra_stylesheets' => $extraStylesheets,
            'hide_layout' => $pageConfig['hide_layout'] ?? false,
        ];

        if ($requestPath === '/goodbye') {
            $clientIp = $defenseService->getClientIp($_SERVER);
            $goodbyeVisitCount = $defenseService->recordGoodbyeVisit($clientIp);
            $extraData['show_video'] = $goodbyeVisitCount > 2;
            $extraData['cache_key_suffix'] = ':video=' . ($extraData['show_video'] ? '1' : '0');
        }

        try {
            $html = $pageRenderer->renderPage(
                $routePath,
                $pageConfig,
                $routeParameters,
                false,
                $locale,
                $extraData
            );
        } catch (RuntimeException) {
            Flight::notFound();

            return;
        }

        $securityHeaders->applyStaticHeaders();
        Flight::response()->header('Content-Type', 'text/html; charset=UTF-8');
        echo $html;
    });
}

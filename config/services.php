<?php

declare(strict_types=1);

use App\Cache\CacheStore;
use App\Core\Container;
use App\Data\DataProviderInterface;
use App\Data\FixtureDataProvider;
use App\Middleware\SecurityHeaders;
use App\Service\PageRenderer;
use App\View\LatteRenderer;

/**
 * Service container configuration
 *
 * @param Container $container
 * @return void
 */
return function (Container $container): void {
    $rootDir = dirname(__DIR__);

    $resolvePath = static function (string $path) use ($rootDir): string {
        $normalizedPath = trim($path);
        if ($normalizedPath === '') {
            return $rootDir;
        }

        if (preg_match('/^(?:[A-Za-z]:[\\\\\\/]|[\\\\\\/]{2}|\\/)/', $normalizedPath) === 1) {
            return $normalizedPath;
        }

        $relativePath = preg_replace('/^[.][\\\\\\/]/', '', $normalizedPath);
        $relativePath = $relativePath === null ? $normalizedPath : $relativePath;
        $relativePath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativePath);

        return $rootDir . DIRECTORY_SEPARATOR . ltrim($relativePath, DIRECTORY_SEPARATOR);
    };

    $fixturesPath = $resolvePath((string) (getenv('FIXTURES_PATH') ?: './fixtures'));
    $cacheDir = $resolvePath((string) (getenv('CACHE_DIR') ?: './cache'));
    $templatesDir = $rootDir . DIRECTORY_SEPARATOR . 'templates';
    $templateCacheDir = $templatesDir . DIRECTORY_SEPARATOR . 'cache';
    $cacheTtl = (int) (getenv('CACHE_TTL') ?: 3600);
    $dataSource = (string) (getenv('DATA_SOURCE') ?: 'fixtures');

    $container->bind('app.root_dir', $rootDir);
    $container->bind('app.templates_dir', $templatesDir);
    $container->bind('app.template_cache_dir', $templateCacheDir);
    $container->bind('app.cache_dir', $cacheDir);
    $container->bind('fixtures_path', $fixturesPath);
    $container->bind('pages_config', require $rootDir . '/config/pages.php');

    $container->singleton(SecurityHeaders::class, static fn (): SecurityHeaders => new SecurityHeaders());

    $container->singleton(
        LatteRenderer::class,
        static fn (Container $container): LatteRenderer => new LatteRenderer(
            (string) $container->get('app.templates_dir'),
            (string) $container->get('app.template_cache_dir')
        )
    );

    $container->singleton(
        CacheStore::class,
        static fn (Container $container): CacheStore => new CacheStore(
            (string) $container->get('app.cache_dir'),
            $cacheTtl
        )
    );

    $container->singleton(
        DataProviderInterface::class,
        static fn (): DataProviderInterface => match ($dataSource) {
            'fixtures' => new FixtureDataProvider($fixturesPath),
            default => throw new RuntimeException('Unsupported DATA_SOURCE: ' . $dataSource),
        }
    );

    $container->singleton(
        PageRenderer::class,
        static fn (Container $container): PageRenderer => new PageRenderer(
            $container->get(DataProviderInterface::class),
            $container->get(LatteRenderer::class),
            $container->get(CacheStore::class)
        )
    );
};

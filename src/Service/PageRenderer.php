<?php

declare(strict_types=1);

namespace App\Service;

use App\Cache\CacheStore;
use App\Data\DataProviderInterface;
use App\View\LatteRenderer;

final class PageRenderer
{
    private string $locale = 'en';

    public function __construct(
        private readonly DataProviderInterface $dataProvider,
        private readonly LatteRenderer $latteRenderer,
        private readonly CacheStore $cacheStore
    ) {
    }

    public function setLocale(string $locale): void
    {
        $normalized = strtolower(trim($locale));
        $this->locale = $normalized === 'ru' ? 'ru' : 'en';

        if (method_exists($this->dataProvider, 'setLocale')) {
            /** @phpstan-ignore-next-line */
            $this->dataProvider->setLocale($this->locale);
        }
    }

    /**
     * @param array<int, string> $dataKeys
     * @param array<string, mixed> $extra
     */
    public function render(string $template, array $dataKeys, array $extra = [], ?string $locale = null): string
    {
        if ($locale !== null) {
            $this->setLocale($locale);
        }

        $templateData = array_replace($this->dataProvider->getMany($dataKeys), $extra);
        $pageHtml = $this->latteRenderer->render('pages/' . $template, $templateData);

        return $this->latteRenderer->render('layout.latte', array_merge($templateData, [
            'content' => $pageHtml,
            'title' => is_string($templateData['title'] ?? null) ? $templateData['title'] : 'Landing page',
            'description' => is_string($templateData['description'] ?? null) ? $templateData['description'] : null,
            'lang' => $this->locale,
        ]));
    }

    /**
     * @param array<int, string> $dataKeys
     * @param array<string, mixed> $extra
     */
    public function renderAndCache(string $cacheKey, string $template, array $dataKeys, array $extra = []): string
    {
        $cachedHtml = $this->cacheStore->get($cacheKey);
        if ($cachedHtml !== null) {
            return $cachedHtml;
        }

        $html = $this->render($template, $dataKeys, $extra);
        $this->cacheStore->set($cacheKey, $html);

        return $html;
    }

    /**
     * @param array<string, mixed> $pageConfig
     * @param array<string, string> $routeParameters
     */
    public function renderPage(
        string $routePath,
        array $pageConfig,
        array $routeParameters = [],
        bool $forceRefresh = false,
        ?string $locale = null,
        array $extraData = []
    ): string {
        if ($locale !== null) {
            $this->setLocale($locale);
        }

        $requestPath = $this->buildRequestPath($routePath, $routeParameters);
        $cacheKey = 'page:' . $requestPath . ':lang=' . $this->locale;
        $cacheKeySuffix = $extraData['cache_key_suffix'] ?? '';
        if (is_string($cacheKeySuffix) && $cacheKeySuffix !== '') {
            $cacheKey .= $cacheKeySuffix;
        }

        if ($forceRefresh) {
            $this->cacheStore->invalidate($cacheKey);
        }

        $pageData = $this->buildPageData($pageConfig, $routeParameters, $extraData);

        return $this->renderAndCache(
            $cacheKey,
            (string) ($pageConfig['template'] ?? 'home.latte'),
            (array) ($pageConfig['data'] ?? []),
            $pageData
        );
    }

    /**
     * @param array<string, mixed> $pageConfig
     * @return array<int, array<string, string>>
     */
    public function getRouteParameterSets(array $pageConfig): array
    {
        $dynamicConfig = $pageConfig['dynamic'] ?? null;
        if (!is_array($dynamicConfig)) {
            return [[]];
        }

        $datasetKey = (string) ($dynamicConfig['dataset'] ?? '');
        $collectionPath = (string) ($dynamicConfig['collection'] ?? '');
        $lookupKey = (string) ($dynamicConfig['lookup'] ?? 'slug');
        $parameterName = (string) ($dynamicConfig['param'] ?? 'slug');

        $dataset = $this->dataProvider->get($datasetKey);
        $collection = $this->extractValue($dataset, $collectionPath);
        if (!is_array($collection)) {
            return [];
        }

        $parameterSets = [];

        foreach ($collection as $item) {
            if (!is_array($item)) {
                continue;
            }

            $parameterValue = $item[$lookupKey] ?? null;
            if (!is_scalar($parameterValue)) {
                continue;
            }

            $parameterSets[] = [
                $parameterName => trim((string) $parameterValue),
            ];
        }

        return $parameterSets;
    }

    /**
     * @param array<string, string> $routeParameters
     */
    public function buildRequestPath(string $routePath, array $routeParameters = []): string
    {
        $resolvedPath = $routePath;

        foreach ($routeParameters as $name => $value) {
            $resolvedPath = str_replace('@' . $name, trim($value), $resolvedPath);
        }

        if ($resolvedPath === '') {
            return '/';
        }

        return $resolvedPath === '/' ? '/' : '/' . trim($resolvedPath, '/');
    }

    /**
     * @param array<string, mixed> $pageConfig
     * @param array<string, string> $routeParameters
     * @param array<string, mixed> $extraData
     * @return array<string, mixed>
     */
    private function buildPageData(array $pageConfig, array $routeParameters, array $extraData): array
    {
        $dataKeys = (array) ($pageConfig['data'] ?? []);
        $datasets = $this->dataProvider->getMany($dataKeys);
        $dynamicData = $this->resolveDynamicData($pageConfig, $datasets, $routeParameters);
        $pageData = array_replace($datasets, $dynamicData, $routeParameters, $extraData);
        $pageData['lang'] = $this->locale;
        $pageData['title'] = $this->resolveTemplate((string) ($pageConfig['title'] ?? 'Landing page'), $pageData);
        $pageData['description'] = $this->resolveDescription(
            (string) ($pageConfig['description'] ?? ''),
            $pageData
        );

        return $pageData;
    }

    /**
     * @param array<string, mixed> $pageConfig
     * @param array<string, array> $datasets
     * @param array<string, string> $routeParameters
     * @return array<string, mixed>
     */
    private function resolveDynamicData(array $pageConfig, array $datasets, array $routeParameters): array
    {
        $dynamicConfig = $pageConfig['dynamic'] ?? null;
        if (!is_array($dynamicConfig)) {
            return [];
        }

        $parameterName = (string) ($dynamicConfig['param'] ?? 'slug');
        $parameterValue = $routeParameters[$parameterName] ?? '';
        if ($parameterValue === '') {
            throw new \RuntimeException('Missing dynamic route parameter: ' . $parameterName);
        }

        $datasetKey = (string) ($dynamicConfig['dataset'] ?? '');
        $collectionPath = (string) ($dynamicConfig['collection'] ?? '');
        $lookupKey = (string) ($dynamicConfig['lookup'] ?? 'slug');
        $itemKey = (string) ($dynamicConfig['item'] ?? 'item');

        $dataset = $datasets[$datasetKey] ?? [];
        $collection = $this->extractValue($dataset, $collectionPath);
        if (!is_array($collection)) {
            throw new \RuntimeException('Dynamic collection not found for route.');
        }

        foreach ($collection as $item) {
            if (!is_array($item)) {
                continue;
            }

            $lookupValue = $item[$lookupKey] ?? null;
            if (!is_scalar($lookupValue)) {
                continue;
            }

            if ((string) $lookupValue === $parameterValue) {
                return [$itemKey => $item];
            }
        }

        throw new \RuntimeException('Dynamic page item was not found.');
    }

    /**
     * @param array<string, mixed> $context
     */
    private function resolveTitle(string $pattern, array $context): string
    {
        if ($pattern === '') {
            return 'Landing page';
        }

        $resolved = $this->resolveTemplate($pattern, $context);

        return $resolved === '' ? 'Landing page' : $resolved;
    }

    private function resolveDescription(string $pattern, array $context): string
    {
        if ($pattern !== '') {
            $resolved = $this->resolveTemplate($pattern, $context);
            if ($resolved !== '') {
                return $resolved;
            }
        }

        $fallback = $this->extractValue($context, 'site.description');
        return is_scalar($fallback) ? (string) $fallback : '';
    }

    private function resolveTemplate(string $pattern, array $context): string
    {
        if ($pattern === '') {
            return '';
        }

        $resolved = preg_replace_callback('/\{([a-zA-Z0-9_.]+)\}/', function (array $matches) use ($context): string {
            $value = $this->extractValue($context, $matches[1]);

            return is_scalar($value) ? (string) $value : '';
        }, $pattern);

        return is_string($resolved) ? $resolved : '';
    }

    /**
     * @param array<string, mixed> $source
     */
    private function extractValue(array $source, string $path): mixed
    {
        if ($path === '') {
            return $source;
        }

        $segments = explode('.', $path);
        $value = $source;

        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return null;
            }

            $value = $value[$segment];
        }

        return $value;
    }
}

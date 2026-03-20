<?php

declare(strict_types=1);

namespace PhpFasty\Core\Data;

use PhpFasty\Core\Locale\LocaleAwareInterface;
use PhpFasty\Core\Locale\LocaleResolverInterface;

final class FixtureDataProvider implements DataProviderInterface, LocaleAwareInterface
{
    private string $locale;

    public function __construct(
        private readonly string $basePath,
        private readonly LocaleResolverInterface $localeResolver
    ) {
        $this->locale = $this->localeResolver->getDefaultLocale();
    }

    public function setLocale(string $locale): void
    {
        $this->locale = $this->localeResolver->normalize($locale);
    }

    public function get(string $key): array
    {
        if (!$this->pathExists($key)) {
            return [];
        }

        $path = $this->buildPath($key);

        $payload = file_get_contents($path);
        if ($payload === false) {
            return [];
        }

        $data = json_decode($payload, true);

        return is_array($data) ? $data : [];
    }

    public function has(string $key): bool
    {
        return $this->pathExists($key);
    }

    public function getMany(array $keys): array
    {
        $datasets = [];

        foreach ($keys as $key) {
            $datasets[$key] = $this->get($key);
        }

        return $datasets;
    }

    private function buildPath(string $key): string
    {
        $normalizedKey = trim($key);
        $localizedPath = $this->basePath . DIRECTORY_SEPARATOR . $this->locale . DIRECTORY_SEPARATOR . $normalizedKey . '.json';
        if (is_file($localizedPath)) {
            return $localizedPath;
        }

        return $this->basePath . DIRECTORY_SEPARATOR . $normalizedKey . '.json';
    }

    private function pathExists(string $key): bool
    {
        $normalizedKey = trim($key);
        if ($normalizedKey === '') {
            return false;
        }

        return is_file($this->buildPath($normalizedKey));
    }
}

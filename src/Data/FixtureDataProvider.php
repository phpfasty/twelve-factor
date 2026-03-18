<?php

declare(strict_types=1);

namespace App\Data;

final class FixtureDataProvider implements DataProviderInterface
{
    public function __construct(
        private readonly string $basePath
    ) {
    }

    public function get(string $key): array
    {
        $path = $this->buildPath($key);
        if (!is_file($path)) {
            return [];
        }

        $payload = file_get_contents($path);
        if ($payload === false) {
            return [];
        }

        $data = json_decode($payload, true);

        return is_array($data) ? $data : [];
    }

    public function has(string $key): bool
    {
        return is_file($this->buildPath($key));
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

        return $this->basePath . DIRECTORY_SEPARATOR . $normalizedKey . '.json';
    }
}

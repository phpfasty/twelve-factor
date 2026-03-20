<?php

declare(strict_types=1);

namespace PhpFasty\Core\Cache;

final class CacheStore
{
    public function __construct(
        private readonly string $cacheDir,
        private readonly int $ttl
    ) {
    }

    public function get(string $key): ?string
    {
        $path = $this->buildPath($key);
        if (!is_file($path)) {
            return null;
        }

        if ($this->isExpired($path)) {
            @unlink($path);

            return null;
        }

        $cachedValue = include $path;

        return is_string($cachedValue) ? $cachedValue : null;
    }

    public function set(string $key, string $html): void
    {
        $this->ensureCacheDirectory();

        $payload = "<?php\n\n";
        $payload .= "declare(strict_types=1);\n\n";
        $payload .= '// cached: ' . gmdate(DATE_ATOM) . ' | key: ' . $key . "\n";
        $payload .= '// ttl: ' . $this->ttl . "\n";
        $payload .= 'return ' . var_export($html, true) . ";\n";

        file_put_contents($this->buildPath($key), $payload, LOCK_EX);
    }

    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    public function invalidate(string $key): void
    {
        $path = $this->buildPath($key);
        if (is_file($path)) {
            @unlink($path);
        }
    }

    public function flush(): void
    {
        if (!is_dir($this->cacheDir)) {
            return;
        }

        $paths = glob($this->cacheDir . DIRECTORY_SEPARATOR . '*.php');
        if ($paths === false) {
            return;
        }

        foreach ($paths as $path) {
            if (is_file($path)) {
                @unlink($path);
            }
        }
    }

    public function getDirectory(): string
    {
        return $this->cacheDir;
    }

    private function isExpired(string $path): bool
    {
        if ($this->ttl <= 0) {
            return false;
        }

        $modifiedAt = filemtime($path);
        if ($modifiedAt === false) {
            return true;
        }

        return ($modifiedAt + $this->ttl) < time();
    }

    private function buildPath(string $key): string
    {
        return $this->cacheDir . DIRECTORY_SEPARATOR . md5($key) . '.php';
    }

    private function ensureCacheDirectory(): void
    {
        if (is_dir($this->cacheDir)) {
            return;
        }

        if (!mkdir($this->cacheDir, 0777, true) && !is_dir($this->cacheDir)) {
            throw new \RuntimeException('Failed to create cache directory: ' . $this->cacheDir);
        }
    }
}

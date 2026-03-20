<?php

declare(strict_types=1);

namespace PhpFasty\Core;

/**
 * Main application class
 *
 * Entry point that initializes the container and boots services.
 */
final class Application
{
    private static ?self $instance = null;

    private ContainerInterface $container;

    private string $basePath;

    private string $servicesPath;

    private function __construct(string $basePath, ?string $servicesPath = null)
    {
        $this->basePath = $this->normalizePath($basePath);
        $this->servicesPath = $this->resolveServicesPath($servicesPath);

        $this->container = new Container();
        $this->loadServices($this->servicesPath);
        $this->container->bootServices();
    }

    public static function getInstance(?string $basePath = null, ?string $servicesPath = null): self
    {
        if (self::$instance === null) {
            self::$instance = new self($basePath ?? self::guessBasePath(), $servicesPath);
        }

        return self::$instance;
    }

    public static function create(?string $basePath = null, ?string $servicesPath = null): self
    {
        return new self($basePath ?? self::guessBasePath(), $servicesPath);
    }

    public function getContainer(): ContainerInterface
    {
        return $this->container;
    }

    public function get(string $serviceId): mixed
    {
        return $this->container->get($serviceId);
    }

    public function getBasePath(): string
    {
        return $this->basePath;
    }

    public function getServicesPath(): string
    {
        return $this->servicesPath;
    }

    private function loadServices(string $path): void
    {
        if (!file_exists($path)) {
            return;
        }

        $configurator = require $path;

        if (is_callable($configurator)) {
            $configurator($this->container);
        }
    }

    private static function guessBasePath(): string
    {
        $cwd = getcwd();
        if (is_string($cwd) && $cwd !== '') {
            return $cwd;
        }

        return dirname(__DIR__, 3);
    }

    private function resolveServicesPath(?string $servicesPath): string
    {
        if ($servicesPath === null || trim($servicesPath) === '') {
            return $this->defaultServicesPath();
        }

        $trimmed = trim($servicesPath);
        if (
            preg_match('/^[A-Za-z]:[\\\\\\/]/', $trimmed) === 1
            || str_starts_with($trimmed, '/')
            || str_starts_with($trimmed, DIRECTORY_SEPARATOR)
            || preg_match('/^[A-Za-z0-9]+:$/', $trimmed) === 1
        ) {
            return $trimmed;
        }

        return $this->basePath . DIRECTORY_SEPARATOR . ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $trimmed), DIRECTORY_SEPARATOR);
    }

    private function defaultServicesPath(): string
    {
        return $this->basePath . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'services.php';
    }

    private function normalizePath(string $path): string
    {
        $trimmed = trim($path);
        if ($trimmed === '') {
            return dirname(__DIR__, 3);
        }

        $normalized = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $trimmed);

        return rtrim($normalized, DIRECTORY_SEPARATOR);
    }
}

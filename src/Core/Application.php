<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Main application class
 *
 * Entry point that initializes the container and boots services.
 * Can be used standalone or integrated with FlightPHP.
 */
class Application
{
    private static ?self $instance = null;

    private ContainerInterface $container;

    private function __construct(?string $configPath = null)
    {
        $this->container = new Container();
        $this->loadServices($configPath);
        $this->container->bootServices();
    }

    public static function getInstance(?string $configPath = null): self
    {
        if (self::$instance === null) {
            self::$instance = new self($configPath);
        }
        return self::$instance;
    }

    public static function create(?string $configPath = null): self
    {
        return new self($configPath);
    }

    private function loadServices(?string $configPath): void
    {
        $path = $configPath ?? dirname(__DIR__, 2) . '/config/services.php';

        if (!file_exists($path)) {
            return;
        }

        $configurator = require $path;

        if (is_callable($configurator)) {
            $configurator($this->container);
        }
    }

    public function getContainer(): ContainerInterface
    {
        return $this->container;
    }

    public function get(string $serviceId): mixed
    {
        return $this->container->get($serviceId);
    }
}

<?php

declare(strict_types=1);

namespace PhpFasty\Core;

/**
 * Container for dependency injection
 *
 * Provides bindings, lazy resolution, singletons, and tagged bootable services.
 */
class Container implements ContainerInterface
{
    public const TAG_BOOTABLE = 'bootable';

    /** @var array<string, mixed> */
    private array $bindings = [];

    /** @var array<string, mixed> */
    private array $instances = [];

    /** @var array<string, callable> */
    private array $factories = [];

    /** @var array<string, string> */
    private array $classMap = [];

    /** @var array<string, array<string, array<string, mixed>>> */
    private array $tags = [];

    public function bind(string $abstract, mixed $concrete, ?string $className = null): void
    {
        $this->bindings[$abstract] = $concrete;

        if ($concrete instanceof \Closure) {
            $this->factories[$abstract] = $concrete;
        }

        if ($className !== null) {
            $this->classMap[$abstract] = $className;
        }
    }

    public function singleton(string $abstract, mixed $concrete, ?string $className = null): void
    {
        $this->factories[$abstract] = $concrete;

        $factory = function ($container) use ($concrete, $abstract): mixed {
            if (!isset($this->instances[$abstract])) {
                $this->instances[$abstract] = $concrete instanceof \Closure
                    ? $concrete($container)
                    : $concrete;
            }

            return $this->instances[$abstract];
        };

        $this->bindings[$abstract] = \Closure::bind($factory, $this, self::class);

        if ($className !== null) {
            $this->classMap[$abstract] = $className;
        }
    }

    public function get(string $abstract): mixed
    {
        if (!isset($this->bindings[$abstract])) {
            throw new \RuntimeException("No binding found for {$abstract}");
        }

        $concrete = $this->bindings[$abstract];

        if ($concrete instanceof \Closure) {
            return $concrete($this);
        }

        return $concrete;
    }

    public function getFactory(string $abstract): ?callable
    {
        return $this->factories[$abstract] ?? null;
    }

    public function getServiceIds(): array
    {
        return array_keys($this->bindings);
    }

    public function hasTag(string $serviceId, string $tag): bool
    {
        return isset($this->tags[$serviceId][$tag]);
    }

    public function addTag(string $serviceId, string $tag, array $attributes = []): void
    {
        $this->tags[$serviceId][$tag] = $attributes;
    }

    public function findTaggedServiceIds(string $tag): array
    {
        $services = [];

        foreach ($this->tags as $serviceId => $tags) {
            if (isset($tags[$tag])) {
                $services[] = $serviceId;
            }
        }

        return $services;
    }

    public function getClassName(string $serviceId): ?string
    {
        return $this->classMap[$serviceId] ?? null;
    }

    public function bootServices(): void
    {
        $bootableServices = $this->findTaggedServiceIds(self::TAG_BOOTABLE);

        foreach ($bootableServices as $serviceId) {
            $service = $this->get($serviceId);

            if ($service instanceof BootableServiceInterface) {
                $service->boot();
            } elseif (method_exists($service, 'register')) {
                $service->register();
            }
        }
    }

    public function has(string $abstract): bool
    {
        return isset($this->bindings[$abstract]);
    }

    public function make(string $abstract, array $parameters = []): mixed
    {
        if (!isset($this->bindings[$abstract])) {
            throw new \RuntimeException("No binding found for {$abstract}");
        }

        $factory = $this->factories[$abstract] ?? null;

        if ($factory instanceof \Closure) {
            return $factory($this, ...$parameters);
        }

        return $this->bindings[$abstract];
    }

    public function extend(string $abstract, \Closure $closure): void
    {
        if (!isset($this->bindings[$abstract])) {
            throw new \RuntimeException("No binding found for {$abstract}");
        }

        $factory = $this->factories[$abstract] ?? null;
        if (!$factory instanceof \Closure) {
            throw new \RuntimeException("Cannot extend non-closure binding for {$abstract}");
        }

        $this->factories[$abstract] = static function (Container $container) use ($factory, $closure): mixed {
            $service = $factory($container);
            return $closure($service, $container);
        };
    }
}

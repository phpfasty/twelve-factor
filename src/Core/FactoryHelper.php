<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Helper for creating services from factory closures.
 */
class FactoryHelper
{
    public static function create(string $abstract, Container $container): mixed
    {
        $factory = $container->getFactory($abstract);

        if ($factory === null) {
            throw new \RuntimeException("Factory for service '{$abstract}' not found");
        }

        return $factory($container);
    }
}

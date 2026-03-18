<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Interface for services that need initialization during application boot.
 */
interface BootableServiceInterface
{
    public function boot(): void;
}

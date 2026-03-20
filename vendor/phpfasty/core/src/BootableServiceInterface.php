<?php

declare(strict_types=1);

namespace PhpFasty\Core;

/**
 * Interface for services that need initialization during application boot.
 */
interface BootableServiceInterface
{
    public function boot(): void;
}

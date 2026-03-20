<?php

declare(strict_types=1);

namespace PhpFasty\Core;

/**
 * Logger Interface
 *
 * Defines a simple logging contract for platform-agnostic implementations.
 */
interface LoggerInterface
{
    public function error(string $message, array $context = []): void;

    public function warning(string $message, array $context = []): void;

    public function info(string $message, array $context = []): void;

    public function debug(string $message, array $context = []): void;
}

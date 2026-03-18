<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Container Interface
 *
 * Defines the contract for a DI container.
 */
interface ContainerInterface
{
    public const TAG_BOOTABLE = 'bootable';

    public function get(string $abstract): mixed;

    public function getServiceIds(): array;

    public function hasTag(string $serviceId, string $tag): bool;

    public function addTag(string $serviceId, string $tag, array $attributes = []): void;

    public function findTaggedServiceIds(string $tag): array;
}

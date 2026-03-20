<?php

declare(strict_types=1);

namespace PhpFasty\Core;

interface ContainerInterface
{
    const TAG_BOOTABLE = 'bootable';

    public function bind(string $abstract, mixed $concrete, ?string $className = null): void;

    public function singleton(string $abstract, mixed $concrete, ?string $className = null): void;

    public function get(string $abstract): mixed;

    public function getFactory(string $abstract): ?callable;

    public function getServiceIds(): array;

    public function hasTag(string $serviceId, string $tag): bool;

    public function addTag(string $serviceId, string $tag, array $attributes = []): void;

    public function findTaggedServiceIds(string $tag): array;

    public function getClassName(string $serviceId): ?string;

    public function bootServices(): void;

    public function has(string $abstract): bool;

    public function make(string $abstract, array $parameters = []): mixed;

    public function extend(string $abstract, \Closure $closure): void;
}

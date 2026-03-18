<?php

declare(strict_types=1);

namespace App\Data;

interface DataProviderInterface
{
    public function get(string $key): array;

    public function has(string $key): bool;

    /**
     * @param array<int, string> $keys
     * @return array<string, array>
     */
    public function getMany(array $keys): array;
}

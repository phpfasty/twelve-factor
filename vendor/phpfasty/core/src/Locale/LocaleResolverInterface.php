<?php

declare(strict_types=1);

namespace PhpFasty\Core\Locale;

interface LocaleResolverInterface
{
    public function normalize(string $locale): string;

    public function getDefaultLocale(): string;
}

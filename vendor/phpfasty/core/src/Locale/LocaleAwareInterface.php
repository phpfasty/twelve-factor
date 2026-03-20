<?php

declare(strict_types=1);

namespace PhpFasty\Core\Locale;

interface LocaleAwareInterface
{
    public function setLocale(string $locale): void;
}

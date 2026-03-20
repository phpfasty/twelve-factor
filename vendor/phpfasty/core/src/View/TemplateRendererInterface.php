<?php

declare(strict_types=1);

namespace PhpFasty\Core\View;

interface TemplateRendererInterface
{
    public function render(string $template, array $params = []): string;
}

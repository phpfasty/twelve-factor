<?php

namespace FlightCms\App\Views;

use Latte\Engine;

class Template
{
    private Engine $latte;
    private string $templatesDir;

    public function __construct(string $templatesDir = null)
    {
        $this->latte = new Engine();
        $this->templatesDir = $templatesDir ?? BASE_PATH . '/app/views';
        
        $this->latte->setTempDirectory(BASE_PATH . '/storage/cache/latte');
        
        $this->latte->addFilter('url', function ($path) {
            return '/' . ltrim($path, '/');
        });
    }

    public function render(string $template, array $params = []): string
    {
        return $this->latte->renderToString("$this->templatesDir/$template.latte", $params);
    }
} 
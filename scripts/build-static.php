<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\View\LatteRenderer;

$rootDir = dirname(__DIR__);
$fixturesPath = getenv('FIXTURES_PATH') ?: $rootDir . '/fixtures';
$fixturesPath = trim((string) $fixturesPath, '/');
$publicPath = $rootDir . '/public';
$templatesPath = $rootDir . '/templates';
$cacheDir = $rootDir . '/templates/cache';

if (!is_dir($cacheDir) && !mkdir($cacheDir, 0777, true) && !is_dir($cacheDir)) {
    throw new RuntimeException('Failed to create template cache directory: ' . $cacheDir);
}

$loadFixture = static function (string $path): array {
    if (!is_file($path)) {
        return [];
    }

    $payload = file_get_contents($path);
    if ($payload === false) {
        return [];
    }

    $data = json_decode($payload, true);
    if (!is_array($data)) {
        return [];
    }

    return $data;
};

$writeHtml = static function (string $relativePath, string $content) use ($publicPath): void {
    $relativePath = ltrim($relativePath, '/');
    $targetPath = $publicPath . '/' . $relativePath;
    $directory = dirname($targetPath);

    if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
        throw new RuntimeException('Failed to create output directory: ' . $directory);
    }

    file_put_contents($targetPath, $content);
};

$renderer = new LatteRenderer($templatesPath, $cacheDir);
$render = [$renderer, 'render'];

$site = $loadFixture($fixturesPath . '/site.json');
$navigation = $loadFixture($fixturesPath . '/navigation.json');
$landing = $loadFixture($fixturesPath . '/landing.json');
$blog = $loadFixture($fixturesPath . '/blog.json');
$about = $loadFixture($fixturesPath . '/about.json');
$projects = $loadFixture($fixturesPath . '/projects.json');
$contact = $loadFixture($fixturesPath . '/contact.json');

$shared = [
    'site' => $site,
    'navigation' => $navigation,
];

$renderPage = static function (
    string $template,
    string $output,
    array $templateData,
    array $baseData,
    callable $render,
    callable $writeHtml
): void {
    $templateData = array_replace($baseData, $templateData);
    $templateData['title'] = $templateData['title'] ?? 'Landing page';

    $pageHtml = $render('pages/' . $template, $templateData);
    $fullHtml = $render('layout.latte', array_merge($templateData, [
        'content' => $pageHtml,
        'title' => $templateData['title'],
    ]));

    $writeHtml($output, $fullHtml);
};

$renderPage(
    'home.latte',
    'index.html',
    [
        'title' => ($site['name'] ?? 'Landing') . ' — Home',
        'landing' => $landing,
        'blog' => $blog,
        'projects' => $projects,
    ],
    $shared,
    $render,
    $writeHtml,
);

$renderPage(
    'blog.latte',
    'blog/index.html',
    [
        'title' => ($site['name'] ?? 'Landing') . ' — Blog',
        'blog' => $blog,
    ],
    $shared,
    $render,
    $writeHtml,
);

$renderPage(
    'about.latte',
    'about/index.html',
    [
        'title' => ($site['name'] ?? 'Landing') . ' — About',
        'about' => $about,
    ],
    $shared,
    $render,
    $writeHtml,
);

$renderPage(
    'projects.latte',
    'projects/index.html',
    [
        'title' => ($site['name'] ?? 'Landing') . ' — Projects',
        'projects' => $projects,
    ],
    $shared,
    $render,
    $writeHtml,
);

$renderPage(
    'contact.latte',
    'contact/index.html',
    [
        'title' => ($site['name'] ?? 'Landing') . ' — Contact',
        'contact' => $contact,
    ],
    $shared,
    $render,
    $writeHtml,
);

if (isset($blog['posts']) && is_array($blog['posts'])) {
    foreach ($blog['posts'] as $post) {
        if (!is_array($post)) {
            continue;
        }

        $slug = isset($post['slug']) && is_string($post['slug']) ? trim($post['slug']) : '';
        if ($slug === '') {
            continue;
        }

        $renderPage(
            'blog-post.latte',
            'blog/' . $slug . '/index.html',
            [
                'title' => ($post['title'] ?? 'Post') . ' — ' . ($site['name'] ?? 'Blog'),
                'post' => $post,
            ],
            $shared,
            $render,
            $writeHtml,
        );
    }
}

echo 'Static site generated.' . PHP_EOL;

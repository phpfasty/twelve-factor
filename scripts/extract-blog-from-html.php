<?php

declare(strict_types=1);

/**
 * One-off: sync blog post body + evidence HTML from .project/html-site/blog into fixtures/en/blog.json.
 */

$root = dirname(__DIR__);
$htmlRoot = $root . '/.project/html-site/blog';
$fixturePath = $root . '/fixtures/en/blog.json';

$slugs = [
    'parsing-20m-per-year',
    'saas-from-zero-to-430k',
    'clickfraud-grant-8m',
    'antibot-architecture',
    'why-redesign-kills-traffic',
    '40m-revenue-2021',
];

function extractFromHtml(string $html): array
{
    $evidenceHtml = '';
    $bodyHtml = '';

    libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    $wrapped = '<?xml encoding="UTF-8"?>' . $html;
    $dom->loadHTML($wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();

    $xpath = new DOMXPath($dom);

    $contentNodes = $xpath->query('//div[contains(concat(" ", normalize-space(@class), " "), " mk-content ")][.//div[contains(concat(" ", normalize-space(@class), " "), " mk-evidence ")]]');
    if ($contentNodes !== false && $contentNodes->length > 0) {
        $evidenceHtml = trim(innerHtml($dom, $contentNodes->item(0)));
    }

    $bodyNodes = $xpath->query('//div[contains(concat(" ", normalize-space(@class), " "), " mk-prose-body ")]');
    if ($bodyNodes !== false && $bodyNodes->length > 0) {
        $bodyHtml = trim(innerHtml($dom, $bodyNodes->item(0)));
    }

    return [
        'evidence_html' => $evidenceHtml,
        'body_html' => $bodyHtml,
    ];
}

function innerHtml(DOMDocument $dom, DOMElement $el): string
{
    $html = '';
    foreach ($el->childNodes as $child) {
        $html .= $dom->saveHTML($child);
    }

    return $html;
}

$data = json_decode((string) file_get_contents($fixturePath), true, 512, JSON_THROW_ON_ERROR);

foreach ($data['posts'] as $i => $post) {
    $slug = (string) ($post['slug'] ?? '');
    if ($slug === '' || !in_array($slug, $slugs, true)) {
        continue;
    }

    $file = $htmlRoot . '/' . $slug . '/index.html';
    if (!is_readable($file)) {
        fwrite(STDERR, "Missing HTML: {$file}\n");
        continue;
    }

    $html = (string) file_get_contents($file);
    $extracted = extractFromHtml($html);

    if ($extracted['body_html'] === '') {
        fwrite(STDERR, "Empty body for slug {$slug}\n");
        continue;
    }

    $data['posts'][$i]['evidence_html'] = $extracted['evidence_html'];
    $data['posts'][$i]['body_html'] = $extracted['body_html'];
    $data['posts'][$i]['url'] = '/blog/' . $slug . '/';
}

$json = json_encode($data, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
file_put_contents($fixturePath, $json . "\n");

echo "Updated {$fixturePath}\n";

<?php

declare(strict_types=1);

return [
    '/' => [
        'template' => 'home.latte',
        'title' => '{site.name} — Home',
        'data' => ['site', 'navigation', 'landing', 'blog', 'projects'],
    ],
    '/blog' => [
        'template' => 'blog.latte',
        'title' => '{site.name} — Blog',
        'data' => ['site', 'navigation', 'blog'],
    ],
    '/blog/@slug' => [
        'template' => 'blog-post.latte',
        'title' => '{post.title} — {site.name}',
        'data' => ['site', 'navigation', 'blog'],
        'dynamic' => [
            'param' => 'slug',
            'dataset' => 'blog',
            'collection' => 'posts',
            'lookup' => 'slug',
            'item' => 'post',
        ],
    ],
    '/about' => [
        'template' => 'about.latte',
        'title' => '{site.name} — About',
        'data' => ['site', 'navigation', 'about'],
    ],
    '/projects' => [
        'template' => 'projects.latte',
        'title' => '{site.name} — Projects',
        'data' => ['site', 'navigation', 'projects'],
    ],
    '/contact' => [
        'template' => 'contact.latte',
        'title' => '{site.name} — Contact',
        'data' => ['site', 'navigation', 'contact'],
    ],
];

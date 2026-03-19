<?php

declare(strict_types=1);

return [
    '/' => [
        'template' => 'home.latte',
        'title' => '{site.name} — {site.titles.home}',
        'description' => '{landing.hero.description}',
        'data' => ['site', 'navigation', 'landing', 'blog', 'projects'],
    ],
    '/blog' => [
        'template' => 'blog.latte',
        'title' => '{site.name} — {site.titles.blog}',
        'description' => '{blog.hero.description}',
        'data' => ['site', 'navigation', 'blog'],
    ],
    '/blog/' => [
        'template' => 'blog.latte',
        'title' => '{site.name} — {site.titles.blog}',
        'description' => '{blog.hero.description}',
        'data' => ['site', 'navigation', 'blog'],
    ],
    '/blog/@slug' => [
        'template' => 'blog-post.latte',
        'title' => '{post.title} — {site.name}',
        'description' => '{post.excerpt}',
        'data' => ['site', 'navigation', 'blog'],
        'dynamic' => [
            'param' => 'slug',
            'dataset' => 'blog',
            'collection' => 'posts',
            'lookup' => 'slug',
            'item' => 'post',
        ],
    ],
    '/blog/@slug/' => [
        'template' => 'blog-post.latte',
        'title' => '{post.title} — {site.name}',
        'description' => '{post.excerpt}',
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
        'title' => '{site.name} — {site.titles.about}',
        'description' => '{about.hero.short} — {about.hero.title}',
        'data' => ['site', 'navigation', 'about'],
    ],
    '/about/' => [
        'template' => 'about.latte',
        'title' => '{site.name} — {site.titles.about}',
        'description' => '{about.hero.short} — {about.hero.title}',
        'data' => ['site', 'navigation', 'about'],
    ],
    '/projects' => [
        'template' => 'projects.latte',
        'title' => '{site.name} — {site.titles.projects}',
        'description' => '{projects.hero.description}',
        'data' => ['site', 'navigation', 'projects'],
    ],
    '/projects/' => [
        'template' => 'projects.latte',
        'title' => '{site.name} — {site.titles.projects}',
        'description' => '{projects.hero.description}',
        'data' => ['site', 'navigation', 'projects'],
    ],
    '/contact' => [
        'template' => 'contact.latte',
        'title' => '{site.name} — {site.titles.contact}',
        'description' => '{contact.hero.description}',
        'data' => ['site', 'navigation', 'contact'],
    ],
    '/contact/' => [
        'template' => 'contact.latte',
        'title' => '{site.name} — {site.titles.contact}',
        'description' => '{contact.hero.description}',
        'data' => ['site', 'navigation', 'contact'],
    ],
    '/goodbye' => [
        'template' => 'goodbye.latte',
        'title' => '{site.name} — {site.titles.goodbye}',
        'description' => '{site.goodbye_description}',
        'data' => ['site'],
        'hide_layout' => true,
        'stylesheets' => ['/static/css/goodbye.css'],
    ],
    '/goodbye/' => [
        'template' => 'goodbye.latte',
        'title' => '{site.name} — {site.titles.goodbye}',
        'description' => '{site.goodbye_description}',
        'data' => ['site'],
        'hide_layout' => true,
        'stylesheets' => ['/static/css/goodbye.css'],
    ],
];

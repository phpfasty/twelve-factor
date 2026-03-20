# phpfasty/core

Reusable runtime primitives for small PHP applications that want a clean boundary
between shared infrastructure and app-specific behavior.

This package was extracted from a real FlightPHP-based application, but it is
kept intentionally framework-light. It does not force a template engine, does
not hardcode supported locales, and does not contain app-specific routes,
content, or business rules.

Repository: [github.com/phpfasty/core](https://github.com/phpfasty/core.git)

## Why

Most small PHP projects start simple, then slowly accumulate framework glue,
template engine assumptions, hardcoded language logic, and deployment-specific
helpers in the same repository.

That works for one site, but it becomes painful when you want to:

- publish the reusable runtime as a package
- build multiple sites on top of the same foundation
- keep templates, routes, fixtures, and UI decisions inside each app
- swap Latte for Twig or another renderer later
- define supported locales in the application instead of the core
- keep deployment predictable with a very small surface area in `vendor/`

`phpfasty/core` exists to keep the shared part small and stable:

- container and bootstrap
- cache storage
- data provider contracts
- renderer contract
- locale contracts
- simple security header middleware

Everything opinionated stays in the consuming application.

## What This Package Includes

- `Application`: tiny application bootstrap wrapper
- `Container`, `ContainerInterface`: simple service container
- `BootableServiceInterface`: contract for services that should boot on startup
- `CacheStore`: file-based HTML/string cache
- `DataProviderInterface`: content source contract
- `FixtureDataProvider`: JSON fixture provider with locale-aware fallback
- `TemplateRendererInterface`: rendering contract, engine-agnostic
- `LocaleResolverInterface`: locale normalization contract
- `LocaleAwareInterface`: contract for services that accept locale updates
- `SecurityHeaders`: minimal response header middleware

## What This Package Deliberately Does Not Include

- no routing layer
- no template engine implementation
- no hardcoded supported locales
- no `.env` loading
- no request-defense logic
- no app-specific pages, controllers, templates, or fixtures

That boundary is intentional.

## Installation

If the package is available through Packagist:

```bash
composer require phpfasty/core
```

If you want to consume it directly from GitHub before publishing to Packagist:

```json
{
  "repositories": [
    {
      "type": "vcs",
      "url": "https://github.com/phpfasty/core.git"
    }
  ],
  "require": {
    "phpfasty/core": "^1.0"
  }
}
```

## Design Principles

### 1. Small shared surface

Only generic runtime building blocks belong here.

### 2. Application owns behavior

The application decides:

- which renderer to use
- which locales are supported
- where content comes from
- which routes exist
- what HTML should be rendered

### 3. Contracts over implementations

The core provides interfaces where the app may need to vary behavior later.

### 4. Simple deployment

The package is intentionally plain PHP with minimal assumptions, which makes it
easy to ship in committed `vendor/`, Docker images, or small PaaS deployments.

## Quick Start

### 1. Create a services file

```php
<?php

declare(strict_types=1);

use PhpFasty\Core\Cache\CacheStore;
use PhpFasty\Core\Container;

return function (Container $container): void {
    $container->bind('app.name', 'Example App');

    $container->singleton(
        CacheStore::class,
        static fn (): CacheStore => new CacheStore(__DIR__ . '/../cache', 3600)
    );
};
```

### 2. Bootstrap the application

```php
<?php

declare(strict_types=1);

use PhpFasty\Core\Application;

require __DIR__ . '/../vendor/autoload.php';

$app = Application::create(
    __DIR__ . '/..',
    __DIR__ . '/../config/services.php'
);

$container = $app->getContainer();
$appName = $container->get('app.name');
```

## Container Example

The container supports simple bindings, singletons, tags, and bootable services.

```php
<?php

declare(strict_types=1);

use PhpFasty\Core\BootableServiceInterface;
use PhpFasty\Core\Container;

final class MetricsService implements BootableServiceInterface
{
    public function boot(): void
    {
        // Initialize metrics exporters, counters, etc.
    }
}

$container = new Container();

$container->bind('app.env', 'production');

$container->singleton(
    MetricsService::class,
    static fn (): MetricsService => new MetricsService()
);

$container->addTag(MetricsService::class, Container::TAG_BOOTABLE);
$container->bootServices();
```

## File Cache Example

`CacheStore` is a tiny file-based cache for storing rendered HTML or other
string payloads.

```php
<?php

declare(strict_types=1);

use PhpFasty\Core\Cache\CacheStore;

$cache = new CacheStore(__DIR__ . '/cache', 600);

$key = 'page:/about';

if ($cache->has($key)) {
    echo $cache->get($key);
    return;
}

$html = '<h1>About</h1>';
$cache->set($key, $html);

echo $html;
```

## Rendering Contract Example

The core does not know or care whether your application uses Latte, Twig, Blade,
or a custom renderer.

It only defines the contract:

```php
<?php

declare(strict_types=1);

namespace PhpFasty\Core\View;

interface TemplateRendererInterface
{
    public function render(string $template, array $params = []): string;
}
```

Example Latte adapter in the consuming app:

```php
<?php

declare(strict_types=1);

namespace App\View;

use Latte\Engine;
use Latte\Loaders\FileLoader;
use PhpFasty\Core\View\TemplateRendererInterface;

final class LatteRenderer implements TemplateRendererInterface
{
    private Engine $latte;

    public function __construct(string $templatesDir, string $cacheDir)
    {
        $this->latte = new Engine();
        $this->latte->setLoader(new FileLoader($templatesDir));
        $this->latte->setTempDirectory($cacheDir);
    }

    public function render(string $template, array $params = []): string
    {
        return $this->latte->renderToString($template, $params);
    }
}
```

Why this matters:

- the core stays engine-agnostic
- each app can choose its own renderer
- migrating from one template engine to another does not require changing core

## Locale Contract Example

The core also does not hardcode which locales are allowed.

Instead, the application defines locale rules and injects them through
`LocaleResolverInterface`.

```php
<?php

declare(strict_types=1);

namespace App\Localization;

use PhpFasty\Core\Locale\LocaleResolverInterface;

final class AppLocaleResolver implements LocaleResolverInterface
{
    public function normalize(string $locale): string
    {
        $supported = ['en', 'es'];
        $normalized = strtolower(trim($locale));

        return in_array($normalized, $supported, true) ? $normalized : 'en';
    }

    public function getDefaultLocale(): string
    {
        return 'en';
    }
}
```

This keeps locale policy where it belongs: in the application.

## Fixture Data Provider Example

`FixtureDataProvider` loads JSON from a base directory and automatically checks
for a localized version first.

Example structure:

```text
fixtures/
  site.json
  blog.json
  en/
    site.json
  es/
    site.json
```

Example usage:

```php
<?php

declare(strict_types=1);

use App\Localization\AppLocaleResolver;
use PhpFasty\Core\Data\FixtureDataProvider;

$provider = new FixtureDataProvider(
    __DIR__ . '/fixtures',
    new AppLocaleResolver()
);

$provider->setLocale('es');

$site = $provider->get('site');
$blog = $provider->get('blog');
```

Resolution behavior:

- first tries `fixtures/<locale>/<key>.json`
- falls back to `fixtures/<key>.json`
- returns an empty array when the file is missing or invalid

## Security Headers Example

`SecurityHeaders` provides a minimal built-in helper for common headers.

```php
<?php

declare(strict_types=1);

use PhpFasty\Core\Middleware\SecurityHeaders;

$headers = new SecurityHeaders();

// For JSON/API responses
$headers->applyApiHeaders();

// For basic HTML/static responses
$headers->applyStaticHeaders();
```

## Recommended Package Boundary

A good split looks like this:

### In `phpfasty/core`

- bootstrap
- container
- cache
- interfaces and contracts
- generic fixture provider
- generic locale contracts
- generic security helpers

### In your application

- routes
- controllers or route closures
- template engine adapter
- locale list and locale switching policy
- content structure
- business logic
- anti-bot and rate-limit logic
- deployment-specific scripts

## Why Not Put Everything Into Core?

Because that usually creates the wrong kind of reuse.

If the package starts owning:

- template engine choices
- locale policy
- page structure
- route definitions
- UI text

then every consuming application becomes harder to customize, harder to upgrade,
and harder to reason about.

This package is useful precisely because it stays boring.

## Stability Expectations

`phpfasty/core` is meant to be:

- small
- explicit
- easy to audit
- easy to vendor
- easy to replace in parts

If you need a full framework, use one.
If you want a thin reusable runtime layer under your own application, this
package is designed for that job.

## License

MIT

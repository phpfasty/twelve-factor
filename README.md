# Twelve-Factor FlightPHP Prototype

This repository is a lightweight FlightPHP application built as a live PHP site with Latte templates, adapter-based data access, and a PHP page cache.

## What is included

- **FlightPHP** for routing and request handling
- **Template rendering** with a pluggable renderer contract in core (`TemplateRendererInterface`) and app-level Latte adapter
- **DataProviderInterface** so content can come from JSON fixtures now and API/blob sources later
- **PHP page cache** for WP-style cached responses stored as PHP files
- **Docker compose skeleton** for PHP-FPM + Nginx deployment (`webdevops/php:8.4-alpine`)
- **APCu support** via dedicated config (`apcu.ini`)
- Local **12-factor style** environment configuration using `.env` and `.env.example`

## Directory layout

- `public/` — Web root and front controller
- `src/` — Application PHP source code
- `config/` — Container, page map, and route configuration
- `templates/` — Latte layout and page templates
- `fixtures/en/` — Default locale (English) JSON content; `fixtures/<locale>/` for other languages (e.g. `ru/`)
- `cache/` — Generated PHP page cache
- `scripts/` — Utility scripts such as cache warmup
- `docker-compose.yml` — PHP + Nginx local container stack
- `nginx/default.conf` — Nginx front-controller config

## Quick setup (local)

1. Install dependencies:

```bash
.php/php/php.exe .php/composer.phar install
```

2. Copy environment defaults:

```bash
cp .env.example .env
```

3. Start the live PHP application:

```bash
.php/php/php.exe -S localhost:8080 -t public public/router.php
```

4. Open `http://localhost:8080`

## Cache warmup

Warm the PHP page cache for all configured routes:

```bash
.php/php/php.exe scripts/build-static.php
```

This script no longer generates static HTML files in `public/`. It renders all configured pages through the same application services and writes cached PHP responses into `cache/`.

## Composer (alternative with global PHP)

If you use a global PHP binary from PATH:

```bash
php .php/composer.phar install
php -S localhost:8080 -t public public/router.php
php scripts/build-static.php
```

## Runtime architecture

- `public/index.php` boots FlightPHP and the application container
- `public/router.php` is the local PHP built-in server router for clean URLs
- `config/pages.php` defines the public page map
- `config/routes.php` registers API and page routes
- `PhpFasty\Core\\Data\\DataProviderInterface` abstracts the content source
- `App\\Service\\PageRenderer` loads data, renders templates through the renderer contract, and stores/retrieves cached pages

Runtime classes such as `Application`, `Container`, `CacheStore`, and `SecurityHeaders` are now extracted into `phpfasty/core` and consumed as a Composer library from this app.  
App-level template rendering is bound in `config/services.php` as `TemplateRendererInterface` → `App\View\LatteRenderer`, so future sites can swap in Twig (or another engine) without touching core.

## Docker setup (optional)

The project includes a compose file with:

- `webdevops/php:8.4-alpine` (PHP-FPM + extensions)
- `nginx:alpine` as the HTTP front

Run:

```bash
docker compose up --build
```

Then visit the configured host and port for the application.

## PHP runtime helpers

To use the local `.php/php` runtime without changing system variables, run:

- `setphp.bat` in Command Prompt
- `.\setphp.ps1` in PowerShell (if execution policy requires, use `-ExecutionPolicy Bypass`)

After running one of them, `php -v` should report the project-local runtime.

## APCu configuration

`apcu.ini`:

```ini
apc.enabled = 1
apc.shm_size = 64M
apc.ttl = 3600
apc.enable_cli = 0
```

## Environment variables

See `.env.example` for baseline settings.

Common values:

- `APP_ENV`
- `APP_DEBUG`
- `CACHE_TTL`
- `CACHE_DIR`
- `DATA_SOURCE`
- `FIXTURES_PATH`
- `PHP_INI_FILE`
- `APCU_INI_FILE`

## Notes

- Page content is rendered through the current app-level renderer (default `App\View\LatteRenderer`) on demand and cached as PHP files in `cache/`
- Current content comes from JSON fixtures through the adapter layer, but the application is prepared for API and blob-backed providers
- If you upgrade PHP locally (for example to 8.4), run the local runtime checks:

```bash
.php/php/php.exe -v
.php/php/php.exe .php/composer.phar -V
```

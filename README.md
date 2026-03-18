# Twelve-Factor FlightPHP Prototype

This repository is a lightweight FlightPHP application scaffolded for a static-first, API-backed architecture.

## What is included

- **FlightPHP** for routing and request handling.
- **Latte** for server-side HTML templating.
- **JSON fixtures** as the primary content source.
- **Local static site generator** that turns fixtures + templates into static HTML pages.
- **Docker compose skeleton** for PHP-FPM + Nginx deployment (`webdevops/php:8.4-alpine`).
- **APCu support** via dedicated config (`apcu.ini`).
- Local **12-factor style** environment configuration using `.env` and `.env.example`.

## Directory layout

- `public/` — Web root and generated HTML output.
- `src/` — Application PHP source code.
- `config/` — Container and framework configuration.
- `templates/` — Latte templates.
- `fixtures/` — JSON content used by the generator.
- `scripts/` — Utility scripts such as `build-static.php`.
- `docker-compose.yml` — PHP + Nginx local container stack.
- `nginx/default.conf` — Nginx config for static/API delivery.
- `.project/` — Internal project notes and principles.

## Quick setup (local)

1. Install dependencies (you can use existing local PHP runtime in `.php/php/php.exe`):

```bash
.php/php/php.exe .php/composer.phar install
```

2. Copy environment defaults:

```bash
cp .env.example .env
```

3. Generate static pages:

```bash
.php/php/php.exe scripts/build-static.php
```

4. Start a local static preview using your local web server:

- `public/index.html` and generated folders under `public/` are updated by the script.

## Composer (alternative with global PHP)

If you use a global PHP binary from PATH:

```bash
php .php/composer.phar install
php scripts/build-static.php
```

## Docker setup (optional)

The project includes a compose file with:

- `webdevops/php:8.4-alpine` (PHP-FPM + extensions)
- `nginx:alpine` as the HTTP front

Run:

```bash
docker compose up --build
```

Then visit the configured host/port for the application.

## PHP runtime helpers

To use the local `.php/php` runtime without changing system variables, run:

- `setphp.bat` in Command Prompt
- `.\setphp.ps1` in PowerShell (if execution policy requires, use `-ExecutionPolicy Bypass`)

After running one of them, `php -v` should report the project-local runtime.

## APCu configuration

- `apcu.ini`:

```
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
- `FIXTURES_PATH`
- `PHP_INI_FILE`
- `APCU_INI_FILE`

## Notes

- Static pages are generated from fixtures + templates and written into `public/`.
- `public/index.html`, `public/blog/`, `public/about/`, `public/projects/`, and `public/contact/` are treated as generated output and ignored by git.
- If you upgrade PHP locally (for example to 8.4), run the local runtime checks:

```bash
.php/php/php.exe -v
.php/php/php.exe .php/composer.phar -V
```

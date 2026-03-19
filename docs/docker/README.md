# Docker setup and project preparation

How to prepare the project for Docker, which images and configs are used, and why.

## Prerequisites

- Docker and Docker Compose
- PHP 8.4+ and Composer for local development (optional if you only run in Docker)

## Quick start

```bash
cp .env.example .env
# Edit .env if needed (PHP_INI_FILE, APCU_INI_FILE are optional)
docker compose up --build
```

Then open `http://localhost:8080`. The app is served by Nginx; PHP runs as PHP-FPM in a separate container.

## Why `webdevops/php:8.4-alpine`

- **PHP 8.4**: Matches the project’s required runtime; allows use of current PHP features and strict types.
- **Alpine**: Small image size and minimal attack surface. Fits “zero redundancy” and fast pulls.
- **webdevops/php**: Image is built for PHP-FPM in container setups: includes FPM, common extensions, and a known layout. Config is overridden by mounting `php.ini` and `apcu.ini` into `/opt/docker/etc/php/` so we don’t need a custom Dockerfile for standard tuning.
- **Alternative**: You could switch to official `php:8.4-fpm-alpine` and add extensions (e.g. APCu) in a custom Dockerfile if you prefer to avoid third-party images; the current choice avoids maintaining that Dockerfile.

## docker-compose.yml overview

- **Services**: `php` (PHP-FPM) and `nginx`. Nginx listens on port 8080 and proxies to PHP.
- **php**:
  - `env_file: .env` — application and Docker config (e.g. `PHP_INI_FILE`, `APCU_INI_FILE`) come from `.env`.
  - Volumes: project root at `/var/www/html`; `php.ini` and `apcu.ini` mounted into the image’s PHP config paths so container uses our settings without rebuilding the image.
  - **Healthcheck**: `php -m | grep -q apcu` ensures APCu is loaded before Nginx is considered ready. Nginx `depends_on` uses `condition: service_healthy` so traffic is only sent once PHP (and APCu) are up.
- **nginx**: Depends on healthy `php`; mounts project root and `nginx/default.conf` (read-only). See [Nginx](nginx/README.md) for the config.

## php.ini

Mounted as the main PHP config (path controlled by `PHP_INI_FILE`, default `php.ini`).

| Directive | Value | Purpose |
|-----------|--------|---------|
| `memory_limit` | 128M | Cap per-request memory. |
| `display_errors` | Off | No PHP errors in output (production-safe). |
| `upload_max_filesize` | 10M | Max upload size. |
| `post_max_size` | 10M | Max POST body; should be ≥ upload limit. |
| `date.timezone` | UTC | Consistent timestamps (Twelve-Factor: explicit config). |

Override by changing `php.ini` or setting `PHP_INI_FILE` in `.env` to another file and mounting that in `docker-compose.yml` the same way.

## apcu.ini

Mounted into PHP’s `conf.d` (path controlled by `APCU_INI_FILE`, default `apcu.ini`).

| Directive | Value | Purpose |
|-----------|--------|---------|
| `apc.enabled` | 1 | Enable APCu. |
| `apc.shm_size` | 64M | Shared memory size for APCu cache. |
| `apc.ttl` | 3600 | Default TTL in seconds for cache entries. |
| `apc.enable_cli` | 0 | Disable APCu in CLI (e.g. `build-static.php`); avoid wasting memory in short-lived scripts. |

**Why APCu in Docker**: Principles call for “APCu (Cache-Aside strategy)” and “maximal performance.” The app’s **page HTML** cache is file-based in `cache/`; APCu is available for future in-process caching (e.g. data, computed values). The healthcheck ensures the extension is loaded so the stack is ready for that use. If you never use APCu in code, you can still leave it enabled for consistency or disable it and adjust the healthcheck.

## Environment variables used by Docker

- **PHP_INI_FILE**: Filename for main PHP config (default `php.ini`). Resolved relative to project root; mounted into the container.
- **APCU_INI_FILE**: Filename for APCu config (default `apcu.ini`). Same idea.
- Other `.env` vars (e.g. `APP_ENV`, `CACHE_DIR`, `DATA_SOURCE`) are passed into the PHP container via `env_file` and used by the application at runtime.

## Project preparation checklist

1. Copy `.env.example` to `.env` and set at least `APP_ENV`, `APP_DEBUG`, `CACHE_DIR`, `DATA_SOURCE`, `FIXTURES_PATH`. Optionally set `PHP_INI_FILE` and `APCU_INI_FILE` if you use custom filenames.
2. Run `composer install` (locally or in a one-off container) so `vendor/` exists; the app needs it at `/var/www/html/vendor`.
3. Ensure `php.ini` and `apcu.ini` exist in the project root (or wherever `PHP_INI_FILE` / `APCU_INI_FILE` point) so the compose mounts are valid.
4. Run `docker compose up --build`. After the PHP healthcheck passes, Nginx will start and serve the app on port 8080.
5. Optional: run cache warmup inside the PHP container, e.g. `docker compose exec php php scripts/build-static.php`, so the file cache is pre-filled.

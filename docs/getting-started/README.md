# Getting started — 101 guide

A step-by-step guide from zero to a running app. No prior knowledge of the codebase assumed.

---

## 1. Prerequisites

- **PHP 8.4+** on your `PATH` as `php`.
- **Composer** on your `PATH` as `composer` ([getcomposer.org](https://getcomposer.org/)).
- **Git** (to clone the repo).
- **Docker & Docker Compose** (optional; only if you want to run with Docker).

---

## 2. Get the code

```bash
git clone <repository-url> twelve-factor
cd twelve-factor
```

---

## 3. Install dependencies

From the project root:

```bash
composer install
```

This creates the `vendor/` directory. The app will not run without it.

Optional (Windows): if you keep a PHP build under `.php\php`, run `setphp.bat` or `.\setphp.ps1` first so `php` resolves to that copy for the current shell.

---

## 4. Environment configuration

Copy the example env file and edit if needed:

```bash
cp .env.example .env
```

Default `.env` is fine for local development. Main variables:

| Variable        | What it does                          | Default   |
|----------------|----------------------------------------|-----------|
| `APP_ENV`      | Environment name (e.g. development)    | production|
| `APP_DEBUG`    | Show Flight errors (true/false)        | false     |
| `CACHE_DIR`    | Directory for page cache files        | ./cache   |
| `CACHE_TTL`    | Cache TTL in seconds                  | 3600      |
| `FIXTURES_PATH`| Path to JSON content files           | ./fixtures|
| `DATA_SOURCE`  | Data provider type (currently: fixtures)| fixtures |

You can leave the rest as in `.env.example`.

---

## 5. Run the app (PHP built-in server)

From the project root:

```bash
php -S localhost:8080 -t public public/router.php
```

You should see something like: `Development Server (http://localhost:8080) started`.

---

## 6. Open the app

In the browser go to:

**http://localhost:8080**

You should see the home page. Try:

- http://localhost:8080/about  
- http://localhost:8080/blog  
- http://localhost:8080/api/health  
- http://localhost:8080/api/landing  

If these work, the app is running correctly.

---

## 7. Run with Docker (optional)

If you prefer Docker:

```bash
docker compose up --build
```

Then open **http://localhost:8080** (Nginx publishes `8080:80` in `docker-compose.yml`). The app runs behind Nginx and PHP-FPM; a one-shot **cache-init** job and **named volumes** for page and template cache are part of the stack — see [Docker](docker/README.md). To pre-fill cache **inside** Docker, run: `docker compose exec php php scripts/build-static.php`.

---

## 8. Project structure at a glance

```
twelve-factor/
├── public/           ← Web root (entry: index.php, router.php)
├── src/              ← Application PHP (Service, View, Defense, Localization, …); DI from phpfasty/core
├── config/           ← services.php, pages.php, routes.php
├── templates/        ← Latte layout and page templates
├── fixtures/en/      ← Default locale JSON (site, landing, blog, …)
├── cache/            ← Generated page cache (created at runtime or by warmup)
├── scripts/          ← CLI scripts (e.g. build-static.php)
├── nginx/            ← Nginx config for Docker
├── .env              ← Your local config (create from .env.example)
├── .env.example      ← Template for .env
├── composer.json     ← PHP dependencies
└── docker-compose.yml
```

- **Request flow**: Browser → `public/index.php` (or router.php) → Flight routes → PageRenderer → Latte templates + data from `fixtures/<locale>/` → HTML (or from cache).
- **Config**: `config/services.php` wires the container; `config/pages.php` defines which pages exist and which data they use.

---

## 9. Key files and what they do

| File | Role |
|------|------|
| `public/index.php` | Front controller: loads env, boots app, runs Flight. |
| `public/router.php` | Used by PHP built-in server: static files + forwards to index.php. |
| `config/services.php` | Registers all services and bindings in the DI container. |
| `config/pages.php` | Page map: URL path → template, data keys, optional dynamic config. |
| `config/routes.php` | Registers HTTP routes (API + one route per page from pages.php). |
| `fixtures/en/*.json` | Content for default locale: site, navigation, landing, blog, etc. Keys match `data` in pages.php. |
| `templates/layout.latte` | Global layout (header, footer). |
| `templates/pages/*.latte` | Page-specific markup. |
| `scripts/build-static.php` | Warms the page cache for all configured pages. |

---

## 10. Change something

**Change content (text):**  
Edit a JSON file in `fixtures/en/` (e.g. `landing.json`). Reload the page. If the cache was already warm, run the warmup again or wait for TTL to see changes:

```bash
php scripts/build-static.php
```

**Add or edit a page:**  
Edit `config/pages.php`: add a path, set `template`, `title`, and `data` keys. Add or adjust a template in `templates/pages/` if needed. No need to touch `config/routes.php` — routes are generated from `pages.php`.

**Change styling:**  
Edit `public/static/css/` and/or templates. Reload.

---

## 11. Cache warmup

To pre-fill the page cache (so the first request is fast):

```bash
php scripts/build-static.php
```

It flushes the cache, then renders every page (including dynamic ones like `/blog/<slug>`) and writes HTML into `cache/`. Same cache is used when you browse the site. Details: [Cache](cache/README.md).

---

## 12. Next steps

- **Templates, CSS, and design:** [Development](development/README.md) — Latte, Tailwind, design tokens, preparing data for templates  
- **Architecture and flow:** [Architecture](architecture/README.md)  
- **Design principles:** [Principles](principles/README.md)  
- **Config and env:** [Configuration](configuration/README.md)  
- **Docker and Nginx:** [Docker](docker/README.md), [Nginx](nginx/README.md)  
- **API and extending it:** [API](api/README.md)  
- **Twelve-Factor compliance:** [Compliance](compliance/README.md)  

Full documentation index: [docs/README.md](../README.md).

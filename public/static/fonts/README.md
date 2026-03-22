# Self-hosted fonts

WOFF2 files and `fonts.css` replace the previous Google Fonts `<link>` in `templates/layout.latte`.

## Bundled families

- **Merriweather** — display (400, 700, 400 italic), unicode subsets as in Google’s CSS.
- **Source Sans 3** — UI (400, 500, 600).
- **JetBrains Mono** — mono (400, 600).

## Updating

1. Request the same Google Fonts CSS2 URL with a **browser User-Agent** (so the response lists `woff2`, not TTF).
2. Extract unique `https://fonts.gstatic.com/...woff2` URLs and download into this folder.
3. Rewrite `src: url(...)` to `url(/static/fonts/<filename>.woff2)` (see existing `fonts.css`).
4. Adjust `<link rel="preload">` in `layout.latte` if primary subsets change.

All fonts are licensed under the **SIL Open Font License**.

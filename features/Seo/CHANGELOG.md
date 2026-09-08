# Changelog

All notable changes to the Seo feature are documented in this file.
A release is the tag `seo-<version>` of dapeio/nino-features.

## 1.0.0 — 2026-09-08

- First release: `/sitemap.xml`, `/robots.txt` and `/llms.txt` generated on
  every request from the persisted routes, their locales and the
  `/webpage<uri>/title`/`description` texts - nothing to maintain by hand,
  and nothing kept under `data/`.
- `/sitemap.xml`: one `<url>` per site page, `<xhtml:link rel="alternate">`
  for every locale variant sharing the same route `uri`, `<lastmod>` from a
  `[template ...]` body's mtime.
- `/robots.txt`: the fixed `Disallow: /_admin/` and `Disallow: /.`, the
  `disallow` and `robots` settings, the `Sitemap:` line and an `agents`-gated
  mention of `/llms.txt`.
- `/llms.txt` (llmstxt.org): heading, description, an optional free block,
  then every titled page grouped by locale once the site has more than one -
  a plain 404, and no mention in robots.txt, while the `agents` setting is
  off.
- Shortcodes `[seo-alternates]` (hreflang links for the current page's
  locale variants plus `x-default`) and `[seo-jsonld]` (a minimal
  Organization + WebSite json-ld block, no nonce needed).
- Settings: `exclude`, `disallow`, `robots` (all `lines`), `agents` (`bool`,
  default on), `description` (`string`, ≤300), `llms` (`text`), `logo`
  (`url`).

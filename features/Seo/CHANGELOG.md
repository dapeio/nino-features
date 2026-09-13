# Changelog

All notable changes to the SEO feature are documented in this file.
A release is the tag `seo-<version>` of dapeio/nino-features.

## 1.1.0 — 2026-09-12

- **A feature can add the pages this one cannot find.** Both documents are
  built from the persisted routes of `config.php`, which is everything a
  site's pages usually are - except where a feature answers a wildcard route.
  The Posts feature is that case: one `GET://blog/*` stands for every post
  there is, and the section index is registered per request rather than
  persisted, so neither the blog nor a single post was in `sitemap.xml` or
  `llms.txt`. `\Nino\Modules\Seo::PAGES` (`/seo/pages`) is fired with an
  empty list and whoever knows those addresses appends them; see the README
  for the shape of an entry.
- An entry may carry a `lastmod` of its own, which the sitemap prefers over a
  template's mtime, and a `title`/`description`, which `llms.txt` prefers
  over the `/webpage<uri>/title` textfills - a page that is one record of
  many has no template to be dated by and no textfill to be titled by.
- A contribution is checked, not trusted: the `exclude` setting, the reserved
  endpoints and the `/_`, `/.` prefixes all apply to it, an address a
  persisted route already carries is not listed twice, and a `lastmod` that
  is not a real calendar date is left out rather than published.

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

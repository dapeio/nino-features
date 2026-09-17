# Changelog

All notable changes to the SEO feature are documented in this file.
A release is the tag `seo-<version>` of dapeio/nino-features.

## Unreleased

### Changed

- **The head markup is a template now.** An alternate link and the JSON-LD
  script were strings `Seo.php` built; they are `templates/alternate-link.tpl`
  and `jsonld.tpl`, filled by token with every value escaped before it goes
  in - see AGENTS.md, "Markup belongs in a template". The sitemap, robots.txt
  and llms.txt are document formats rather than views and stay where they
  are. The output is the same.

### Fixed

- **An invalid byte in a value rendered as nothing.** `htmlspecialchars()`
  answers input that is not valid UTF-8 with `''` unless `ENT_SUBSTITUTE` is
  among its flags, and every call here spelled the flags out without it.
  Every call carries it now, as the kernel's do, and
  `tests/escaping-smoke.php` reads every feature for the next one.

- **Contributed pages were deduplicated by scanning a list.** `_contributed()`
  kept the paths it had taken as a list and asked `in_array()` once per
  contribution, so a site whose blog contributes n posts did on the order of
  n²/2 string comparisons - on every page view that renders
  `[seo-alternates]`, and on every sitemap, robots.txt and llms.txt. Measured
  on this machine, per page view:

  | posts | before | after |
  | --- | --- | --- |
  | 1000 | 3.5 ms | 1.8 ms |
  | 3000 | 18.1 ms | 5.5 ms |
  | 10000 | — | 17.8 ms |

  Keyed instead of listed, so the cost grows with the posts rather than with
  their square. What the deduplication does is unchanged: a contribution the
  persisted routes already carry is still dropped, and so is one an earlier
  contribution already named.

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

# Changelog

All notable changes to the SEO feature are documented in this file.
A release is the tag `seo-<version>` of dapeio/nino-features.

## Unreleased

- Needs Nino `^1.3`, where the constraint said `^1.1`. The sectioned `manual`
  map this manifest carries is only read by a kernel newer than the
  `v1.2.0-beta` tag - `Features::manifest()` refuses it on the tagged one -
  and `^1.1` is satisfied by that kernel, so the catalogue offered the
  feature to an installation that could not then install it. `^1.3` names
  only a kernel that can read the manifest.

### Added

- **A feature can keep pages out of the sitemap: `/seo/exclude`.** The
  opposite of `/seo/pages`: fired with an empty list, an answer appends
  patterns spelled like the **Never list these** setting (`/members` for that
  page, `/members/*` for it and everything below it). They add to the
  operator's own lines, never replace one, and cover persisted and
  contributed pages alike. The Protected area feature answers it with its
  prefixes while a password is set. `robots.txt` stays as it was.

### Changed

- **The German manual called `[seo-jsonld]` „Ihre strukturierten Daten“.** The
  English says "Its structured data": the block belongs to the page. Alone in
  the Features panel the German read like a formal „Your“. It says „Die
  strukturierten Daten der Seite“ now. Words only.

- **The directory listing had no `templates/` in it, and the class pointed at
  a path the base install does not have.** The README still listed
  `feature.php`, `Seo.php` and `tests/` alone and said the feature owns no
  visitor-facing content, where the alternate link and the JSON-LD block are
  `templates/alternate-link.tpl` and `jsonld.tpl` now. In the class docblock
  the persisted sitemap/robots/llms routes were credited to
  `units/base/manifest.php`; the file is
  `_admin/install/library/base/manifest.php`, which is what the README has
  said all along. Words only - the code is unchanged.

- **The head markup is a template now.** An alternate link and the JSON-LD
  script were strings `Seo.php` built; they are `templates/alternate-link.tpl`
  and `jsonld.tpl`, filled by token with every value escaped before it goes
  in - see AGENTS.md, "Markup belongs in a template". The sitemap, robots.txt
  and llms.txt are document formats rather than views and stay where they
  are. The output is the same.

### Fixed

- **sitemap.xml and llms.txt listed the 404 page and every error page.** A
  page was any persisted `GET` route, so the wizard's `GET://404` - a route
  that answers with status 404 - was offered to crawlers with its title, as
  was any other route carrying a `statusCode` of 400 or more. The kernel's
  not-found route `GET://404` and every route whose `statusCode` is 400 or
  more are left out of both now.

- **A title, an address or a description could end a link in llms.txt
  halfway through itself.** An entry there is `- [title](url): description`
  and nothing in it was held to that shape: a `]` in a title closed the link
  text where it stood and left the rest of the title in the document as
  prose, a `)` in an address - `/blog/pin(1)` - cut the address off
  mid-slug, and a description textfill somebody had wrapped over two lines
  ended the list item and put the rest of the sentence in as a paragraph of
  its own, under a page it had nothing to do with. Titles now go in with
  their brackets escaped the way CommonMark takes an ASCII punctuation
  character literally, addresses with their parentheses percent-encoded -
  which is what those are in a url anyway - and every value on one line. The
  heading and the site description under it are held to one line for the
  same reason.

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

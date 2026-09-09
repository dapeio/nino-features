# SEO

**Key:** `seo` · **Class:** `\Nino\Modules\Seo` · **Version:** 1.0.0 · **Nino:** `^1.1`

What search engines and AI agents ask a site for, generated from what Nino
already knows - the persisted routes under `/nino/http/routes`, their
locales, and the `/webpage<uri>/title` and `/webpage<uri>/description` texts
the wizard's Webpages step (or a hand edit of `/text/<locale>.php`) already
writes. **There is nothing here to maintain by hand**: every response is
built fresh from `config.php` on every request, so a page added, renamed or
removed in the routes shows up the next time any of these three files is
fetched, with no second place to remember to update.

One directory, the shape the [feature recipe](https://github.com/dapeio/nino/blob/main/docs/recipes/feature.md)
describes: `feature.php`, `Seo.php`, `tests/`. There is no panel and no
install unit - nothing here needs a workbench screen of its own, and the
feature owns no visitor-facing content beyond the three technical endpoints
`init()` registers itself, the same way `Modules\Newsletter` owns
`/.newsletter` or the feature recipe's `Catalog` owns `/api/catalog`. The
changes per version are in [CHANGELOG.md](CHANGELOG.md).

## What a "page" is

Every persisted `GET://...` route of `config.php`'s `/nino/http/routes` -
read directly from `config.php`, the way `\Nino\Features::activate()` itself
reads routes to apply a unit against, never the live array, which also
carries this request's own runtime routes (the workbench's, every active
module's own) - counts as a site page unless its external path (the part of
the route key after `GET:/`, ie. what a browser actually requests):

- is one of this feature's own three endpoints (`/sitemap.xml`,
  `/robots.txt`, `/llms.txt`) - never listed as a page of itself, whatever a
  persisted route happens to say about it;
- starts with `/_` (the workbench) or `/.` (a module's own technical
  endpoint, `/.form`, `/.newsletter`, `/.protected`, ...);
- matches a line of the **Never list these** setting (`exclude`) - an exact
  path, or `/path/*` for that path and everything below it.

## Locale variants

Two routes are locale variants of the *same* page when they share the same
`uri` field - the internal identity `\Nino\Http::findRouteUri()` itself
pairs a locale switch against - with different `locale` values, exactly the
shape `tests/kernel-smoke.php` exercises with its own
`GET://rechtliches` (`uri => /legal, locale => de_DE`) /
`GET://legal` (`uri => /legal, locale => en_US`) pair. A `<loc>`, an
`href`, or a link in `llms.txt` always carries the *external* path - what a
crawler can actually fetch - never the internal `uri`, which need not be
reachable on its own (see `tests/fixtures/features/Sample/install/manifest.php`'s
`GET://sample-de` → `uri: /beispiel` for exactly such a case).

## `/sitemap.xml`

One `<url>` per page, `<loc>` an absolute url (`https://` + the **Website
url** setting's value + the external path), an
`<xhtml:link rel="alternate" hreflang="...">` for every locale variant that
carries an explicit `locale` (BCP47, `de_DE` → `de-DE`) - including the page
itself - and a `<lastmod>` where the route's own `body` is exactly
`[template /path]`, the mtime of `/path.tpl` in `Y-m-d`; omitted for
anything else (a route answered by a callback, an inline body, an empty
one). `Content-Type: application/xml; charset=UTF-8`.

## `/robots.txt`

```text
User-agent: *
Disallow: /_admin/
Disallow: /.
Disallow: <one line per the "Additional robots.txt disallows" setting>
Sitemap: https://<website url>/sitemap.xml
# llms.txt: https://<website url>/llms.txt      (only while "agents" is on)
<one line per the "Additional robots.txt lines" setting, verbatim>
```

`Content-Type: text/plain; charset=UTF-8`.

## `/llms.txt`

The [llmstxt.org](https://llmstxt.org) convention in two sentences: a plain
Markdown file, `# <name>` and a one-line `> <description>` followed by
whatever a page's own README would say, so an AI agent gets a short,
structured answer instead of having to crawl and parse the whole site.
This feature's shape:

```markdown
# <company name>

> <the "Site description" setting>

<the "llms.txt free text" setting, verbatim, if set>

## Pages

### en-US                                  (only with more than one locale)

- [<title>](<url>): <description>
- ...

### de-DE

- ...
```

A page's title and description come from the `/webpage<uri>/title` and
`/webpage<uri>/description` texts - the same fills `html-header.tpl` uses -
looked up in the page's own locale (its `locale` field, else the site's
native locale, the same fallback a locale-agnostic route renders under). A
page without a title is left out rather than listed empty. With only one
locale the `### ...` headings are left out too - `## Pages` lists every page
flat. `Content-Type: text/plain; charset=UTF-8`.

**`agents`** (default on) switches this file on and off: off answers a
plain `404` at `/llms.txt` - always registered as a route regardless of the
setting, the same "answer 404 from inside the callback" shape the feature
recipe's `Catalog` uses for its own `public` setting, so a project's
wizard-written, persisted `/llms.txt` route (if any - see "Overwriting the
wizard's static templates" below) is reliably replaced rather than left to
answer a stale static file - and drops the `# llms.txt: ...` line from
`robots.txt`.

## `[seo-alternates]`

Placed in a project's `html-header.tpl`, in `<head>`, next to the base
install's own `canonical` link: renders the `<link rel="alternate"
hreflang="...">` lines for the *current* page's locale variants (found the
same way `[seo-alternates]`'s neighbour `Modules\Navigation` finds the
current uri from inside a shortcode - `\Nino\Http::getRequest()`, since a
shortcode never sees the request array itself) plus one `hreflang="x-default"`
pointing at the native-locale variant. Renders `''` on a page with no
variant, or none the feature recognizes, so it is safe to place
unconditionally on every page:

```html
<link rel="canonical" href="https://[[/website/url]][[/nino/http/request/uri]]">
[seo-alternates]
```

## `[seo-jsonld]`

A minimal `Organization` + `WebSite` json-ld block - name and url from the
`/company/name` and `/website/url` texts, a `logo` from the **Logo url**
setting when one is set - placed anywhere in the page, `html-header.tpl`'s
own `<head>` next to its `LocalBusiness` block being the natural spot. No
nonce: a `<script type="application/ld+json">` is never *executed* by a
browser in the first place (the html spec's "prepare the script element"
step only runs a script whose type is empty, a javascript mime type,
`module` or `importmap` - anything else, including this one, is left as an
inert data block), so it is not subject to the kernel's
`Content-Security-Policy` `script-src`/`default-src` at all, the same reason
`html-header.tpl`'s own `LocalBusiness` block carries none either. `</` is
still neutralized (`JSON_HEX_TAG`) on the way in, the same defence
`Modules\Jstext` and `Html::doJsonShortcode()` apply to admin-editable text
going into an inline `<script>`.

## Overwriting the wizard's static templates

The base install's own `_admin/install/library/base/manifest.php` persists
`GET://sitemap.xml`, `GET://robots.txt` and `GET://llms.txt` at setup time,
each with a static `[template /templates/...]` body the wizard fills in
once from whichever pages were picked in its Webpages step - and nobody
updates again as pages come and go. This feature's `init()` registers the
same three route keys with its own callback, which is a plain assignment
into the live routes array: the same "a stale persisted entry cannot shadow
its behavior" reasoning the feature recipe's `Catalog` docblock spells out
for its own technical route. Activating this feature therefore takes over
serving all three from the moment it is switched on, for as long as it stays
active; deactivating it simply stops overwriting them, so whatever a project
had before - the static templates, or nothing - answers again. The
now-unused `robots.tpl`, `sitemap-xml.tpl` and `llms-txt.tpl` stay on disk,
harmless.

## Settings

| Setting | Type | Default | |
| --- | --- | --- | --- |
| `exclude` | `lines` | *(empty)* | One uri per line, `/path` or `/path/*`: left out of the sitemap and llms.txt |
| `disallow` | `lines` | *(empty)* | One path per line, added to robots.txt as its own `Disallow:` line |
| `robots` | `lines` | *(empty)* | Appended to robots.txt verbatim, after the Sitemap line |
| `agents` | `bool` | `true` | Publishes `/llms.txt`; off answers a 404 there and drops it from robots.txt |
| `description` | `string`, ≤300 | *(empty)* | The site's one-line description, under the heading in llms.txt |
| `llms` | `text` | *(empty)* | An optional free block in llms.txt, after the description |
| `logo` | `url` | *(empty)* | Added to `[seo-jsonld]`'s Organization block when set |

Read through `\Nino\Features::setting()`/`settings()`, saved through the
Features panel's settings form like any other feature - see
[docs/features.md](https://github.com/dapeio/nino/blob/main/docs/features.md).

## What it does not do

- No `<title>`, `meta description`, canonical link or Open Graph tags - the
  base install's own `html-header.tpl` already renders every one of those
  from `/webpage<uri>/title`, `/webpage<uri>/description`, `/website/url`
  and `/company/name`. This feature adds only what that template does not:
  the machine-readable files, the hreflang alternates, and a json-ld block.
- No keyword research, no ranking reports, no analytics of any kind - see
  the catalogue's `Stats` feature for page-view counts, kept entirely
  separate on purpose.
- No sitemap for anything but pages: no images, no videos, no news
  sitemap extensions.
- No per-page control over what goes into `[seo-jsonld]` - it is
  deliberately minimal, site-wide `Organization`/`WebSite` data only. A
  page that needs its own structured data (an `Article`, a `Product`) adds
  its own `<script type="application/ld+json">` in its own template.

## Tests

`tests/seo-smoke.php` is the feature's own test: the manifest and activation
through `\Nino\Features`, a small fixture of persisted routes (a de_DE/en_US
pair, a plain page whose uri carries a `&`, an excluded page, a
`/.internal` endpoint, a `/_admin/x` tool uri), `sitemap.xml` (well-formed,
exactly the site pages as absolute urls, hreflang alternates for the paired
page, `&` escaped, a lastmod from a template's mtime and none where there is
no template), `robots.txt` (the fixed lines, the settings, the sitemap and
llms.txt mentions, in order), `llms.txt` (heading, description, titled pages
grouped by locale, and a 404 - with no mention in robots.txt - once `agents`
is off), `[seo-alternates]` and `[seo-jsonld]`, and deactivation. It loads
Nino's `tests/harness.php` from the checkout three levels up - where the
feature sits in a project - or from the one `NINO_ROOT` names, and defines
`NINO_FEATURES_DIR` as this feature's parent directory, so the kernel serves
the class from wherever the feature is:

```bash
php features/Seo/tests/seo-smoke.php
NINO_ROOT=../nino php features/Seo/tests/seo-smoke.php
```

With the feature copied into a checkout, Nino's `tests/features-smoke.php`
validates the manifest as well, and PHPStan analyses the class.

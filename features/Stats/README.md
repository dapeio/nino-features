# Stats

**Key:** `stats` · **Class:** `\Nino\Modules\Stats` · **Version:** 1.0.0 · **Nino:** `^1.1`

Page-view counts for the workbench - counts, and only counts. No cookie is
set, no ip address is read, no fingerprint of any kind is derived and
nothing is stored per visitor: **there is no personal data in this feature
at all**, so no consent banner has to appear for it and nothing can be
requested or deleted about a "visitor" under the GDPR, because no visitor is
ever identified, tracked, or distinguished from any other. What is kept is
three numbers per day - a total, a per-uri breakdown, a per-referrer-host
breakdown - the same shape an access log's `wc -l` and `awk` would produce,
just aggregated as it happens instead of parsed after the fact.

One directory, the shape the [feature recipe](https://github.com/dapeio/nino/blob/main/docs/recipes/feature.md)
describes: `feature.php`, `Stats.php`, `Admin/Admin.php`, `assets/`,
`text/`, `tests/`. There is no install unit - the feature ships nothing for
the public site, only the counter and the workbench panel. The changes per
version are in [CHANGELOG.md](CHANGELOG.md).

## What is counted, and where

`init()` registers `callbackCount()` on `/nino/http/response` at **priority
8** - one below `Modules\Cache`'s own priority-9 handler on the same hook
(see `_nino/Nino/Modules/Cache/Cache.php`, `callbackResponse()`). Priorities
run low to high, so this counts a view *before* a cache hit answers the
request and ends it: a page served straight from the cache is still one
view. Only the render is skipped on a hit, never the count.

A response is counted when all of the following hold:

- the method is `GET` - the raw method too, so a `HEAD` folded to `GET` for
  routing (see `\Nino\Http::_cleanRawMethod()`) is not one;
- the status code is `200`;
- the uri is not `/_admin` or anything under it, not a dot-prefixed module
  endpoint (`/.form`, `/.nino/auth/login`, this catalogue's own
  `/.newsletter`, ...), and not a `/nino/...` uri - the same "not one of the
  tools" boundary `Modules\Cache` draws for what it will never cache,
  replicated in `Stats::_isTool()` because `Cache::_isTool()` itself is
  private;
- the uri does not match a line under the **Never count these** setting;
- the visitor is not signed in to the workbench, unless **Count signed-in
  visitors** is switched on.

The uri stored is the request uri with its query string already gone - not
stripped by this feature, but because `\Nino\Http::cleanUri()` never puts a
query string into `$request['/nino/http/request']['uri']` in the first
place, so `?utm_source=...` and the like are dropped before this ever runs.

## What is *not* counted, and why there is no unique-visitor number

No cookie, no session, no ip address, no user-agent, no canvas or font
fingerprint - nothing that could tell one visitor from another across two
requests. That is a deliberate ceiling, not a missing feature: a
unique-visitor estimate needs *some* way to recognize "the same visitor
again", and every way of doing that is personal data under the GDPR - an ip
address outright, a cookie, and a fingerprint or a salted-per-visitor hash
just as much, because a hash that lets you say "this is the same person as
yesterday" is exactly the property that makes it personal data, salted or
not. The moment this feature could answer "how many *different* people
visited today", it would owe a consent banner, a retention policy for that
identifier, and an answer to an access or erasure request under Art. 15/17 -
none of which this feature is built to carry, on purpose.

What it offers instead, as a second, weaker number: **visits**, approximated
as views with an external or empty referrer (see "Referrers" below) - a
navigation that arrived from outside the site, or typed the address, or
followed a bookmark. This overcounts a visitor who opens several pages in a
row without a referrer surviving (a link opened in a new tab, most privacy
browsers) as several "visits", and it is called what it is in the panel and
here, not "unique visitors" - it never claims to recognize a person.

## Referrers

The `Referer` request header's host, lowercased, `parse_url()`-extracted -
nothing else about the referring page or the client is read. Two cases are
dropped rather than counted:

- an empty or missing header (no referrer at all - most direct navigation,
  most native apps, most privacy-conscious browsers that strip it);
- the site's own host (comparing against the request's `Host` header,
  itself stripped of a port) - an internal click from one page to another is
  not "traffic from elsewhere".

## Storage

One file per month, `/data/stats/YYYY-MM.php`:

```php
[
	'days' => [
		'2026-09-08' => [
			'total'     => 142,
			'uris'      => [ '/' => 58, '/blog/post-1' => 12, '/…' => 3 ],
			'referrers' => [ 'example.org' => 9, 'search.example' => 4 ],
		],
		// ...one entry per day that had at least one counted view
	],
]
```

Every counted view is one `\Nino\Filesystem::mutate()` call against that
month's file - locked, read-modify-write, atomic. Nothing is buffered or
batched: the file on disk after a view is counted is always the true count
up to and including that view.

### The uri budget

**Distinct uris per day** (`maxUris`, default 500, 50 to 5000) bounds the
`uris` map for one day. Once that many distinct uris have been seen that
day, every further *new* one is folded into a single `'/…'` bucket instead
of getting its own key - a crawler that walks thousands of never-repeated
uris (a faceted search, a calendar with a page per day going back decades)
fills that one bucket instead of the day's file. An already-counted uri
keeps incrementing its own key regardless of when in the day it was first
seen; only a **new** uri past the budget is folded.

### Retention

**Keep for** (`retentionMonths`, default 13, 1 to 60) months. Checked once,
on the first counted view of a new calendar day - not on every view - via
`\Nino\RotatingLog::prune()` over `/data/stats/`, deleting any `YYYY-MM.php`
whose month is entirely before the cutoff. A file this sweep does not
recognize as one of its own (wrong name shape) is left alone, the same rule
every `RotatingLog::prune()` caller gets.

### The manifest's `data` entry

`'/data/stats'` - the whole directory, so the workbench's daily backup
carries every month file. There is nothing here a restore has to *merge*
rather than overwrite (unlike a subscriber list, where a restore must not
resurrect someone who unsubscribed since the backup): a count going
backwards to an earlier backup's count is not a GDPR problem, and neither
direction of the restore can make yesterday's total wrong in a way that
matters to an operator looking at a chart. `init()` therefore registers no
`/nino/admin/restore` callback - the plain whole-`data/`-directory copy the
backup already does is enough.

## Settings

| Setting | Type | Default | |
| --- | --- | --- | --- |
| `countSignedIn` | `bool` | `false` | Count a page opened by someone signed in to the workbench. Off by default, so an editor working on the site does not skew the numbers |
| `exclude` | `lines` | *(empty)* | One uri per line: `/internal` (exact) or `/internal/*` (that uri and everything under it) |
| `maxUris` | `int`, 50-5000 | `500` | The per-day uri budget described above |
| `retentionMonths` | `int`, 1-60 | `13` | How many monthly files to keep |

Read through `\Nino\Features::setting()`/`settings()`, saved through the
Features panel's settings form like any other feature - see
[docs/features.md](https://github.com/dapeio/nino/blob/main/docs/features.md).

## The panel

`\Nino\Modules\Stats\Admin` is answered by `adminPanels()`, so it is in the
workbench exactly while the feature is active - after activating or
deactivating, reload the page. It is entirely read-only: nothing on it
writes anything beyond the settings form every feature already gets.

| | |
| --- | --- |
| Navigation | **Stats** in the Content group (uri `stats`, position 70) |
| Permission | `/_admin/stats/view` on every action - a content permission, offered on the Users panel's roles tab; the **Editor** role does not receive it by itself, an operator grants it there |
| Actions | `stats/months` (`apiMonths()`): every month that has a file, newest first · `stats/month` (`apiMonth()`): one month's numbers, `{ month }` validated as `YYYY-MM`, else `400` |
| `stats/month` answers | `{ month, days: [ { day, total } ], totals: { views, days }, uris: [ { uri, views } ], referrers: [ { host, views } ] }` - `uris` and `referrers` are the month's totals across every day in it, top 50 each, most-viewed first |
| Dashboard | `summary()` gives the Dashboard a tile: views over the last 7 days (today included), labelled `/_admin/stats/label/tile`. The panel contract's tile only ever carries `{ value, label }` (see `\Nino\Admin\Panels::collect()`), so today's count alone is not shown as a separate number there - only on the panel's own pane, as the first bar of the current month |
| Activity log | `log()` always answers `''` - opening the panel and looking at a chart is not something the activity log has any use recording |
| Assets | `assets/admin.js`, `assets/admin.css`, named through `\Nino\Admin\Panels::relative()` so they move with the directory |
| Text | `text/en_US.php` and `text/de_DE.php` |

The pane offers a month selector, a plain-css bar per day of the selected
month (a `<div>` per day, its height a percentage of that month's busiest
day - no chart library), and the two top-50 tables (pages, referrer hosts)
built from the shared, searchable/sortable admin table component.

## The cost

One locked file write per counted view. For a small site - a few thousand
counted views a day at most, one process serving requests at a time or a
handful in parallel - that is the same order of cost as any other Nino
write (a form submission, an Element save) and is not something an operator
needs to think about. It is not built for a busy site: at a high enough
request rate, every view briefly locks the current month's file
(`/data/.locks/`, see `\Nino\Filesystem`), and views serialize on that lock
the way any other `mutate()` caller's writes do. There is no batching,
sampling, or async queue in this version - if that cost ever shows up on a
real site, sampling (count 1 in N, multiply back) is the natural next
version, deliberately left out of this one for the sake of an exact count.

## What it does not do

- No events or goals - a click, a form submission, a scroll depth. Page
  views only.
- No bot filtering beyond excluding the workbench's own requests (`/_admin`,
  the dot-prefixed module endpoints) - a crawler that requests ordinary
  pages is counted like anyone else, the same way a plain access log would
  count it. The `maxUris` fold keeps one particularly thorough crawler from
  filling a day's file; it does not keep it out of the total.
- No unique-visitor or session count, no bounce rate, no time-on-page,  no
  device/browser/OS breakdown, no country or city - every one of those needs
  either personal data or a scope this version deliberately does not take
  on. See "What is *not* counted" above.
- No export. The panel's tables are read on screen; the month files
  themselves are plain, human-readable `<?php return [...];` under
  `/data/stats/` for anyone who wants to script something over them
  directly.

## Tests

`tests/stats-smoke.php` is the feature's own test: the manifest and
activation through `\Nino\Features`, what is and is not counted (method,
status, the tool/dot/exclude boundaries, a signed-in visitor, `countSignedIn`,
a query string dropped, a referrer's own host dropped), the storage shape,
the `maxUris` fold, retention on the first count of a new day, a cache hit
still counting (driven through the real `/nino/http/response` callback chain
so the priority-8-before-9 ordering is genuine, not asserted by construction -
the hit itself runs in a subprocess, since `Modules\Cache` answers one by
calling `\Nino\Http::output()`, which `exit()`s), the panel's two actions
with their permission and a `400` for an invalid month, the dashboard tile,
and deactivation. It loads Nino's `tests/harness.php` from the checkout three
levels up - where the feature sits in a project - or from the one
`NINO_ROOT` names, and defines `NINO_FEATURES_DIR` as this feature's parent
directory, so the kernel serves the class from wherever the feature is:

```bash
php features/Stats/tests/stats-smoke.php
NINO_ROOT=../nino php features/Stats/tests/stats-smoke.php
```

With the feature copied into a checkout, Nino's `tests/features-smoke.php`
validates the manifest as well, and PHPStan analyses the class and the
panel.

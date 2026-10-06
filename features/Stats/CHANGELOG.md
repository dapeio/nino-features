# Changelog

All notable changes to the Stats feature are documented in this file.
A release is the tag `stats-<version>` of dapeio/nino-features.

## Unreleased

- **The panel uses the workbench's request helper and its wording of a failure,
  where the Nino has them.** Every request goes through `Nino.adminUi.api`,
  which signs the page in again over what is on screen when the session has
  ended instead of losing it. The panel only reads, so nothing is registered
  with the shell's question about unsaved input. On a Nino without the helper
  (1.3.x) the panel posts as it did, but from the project's own directory: it
  posted to `/_admin/` from the root of the domain, which a project in a
  subdirectory does not answer. `nino` stays `^1.3`.

- The README says where the settings are: in the feature's **Settings** - the
  Features panel's form for it, or a **Settings** tab of the Stats panel on a
  Nino that has one.

- **The bar row is the month.** The store holds a day once it has a view,
  and the panel drew exactly those days, so a month with one visit was one
  bar the width of the panel and no calendar around it. The row draws every
  day of the month now - the empty ones as a baseline mark with their
  number. A new `stats-js-smoke.js` draws the row over a dom stand-in
  and holds the calendar, the heights and the edges; `stats-smoke.php`
  runs it where node is on the path.

- Needs Nino `^1.3`, where the constraint said `^1.1`. The sectioned `manual`
  map this manifest carries is only read by a kernel newer than the
  `v1.2.0-beta` tag - `Features::manifest()` refuses it on the tagged one -
  and `^1.1` is satisfied by that kernel, so the catalogue offered the
  feature to an installation that could not then install it. `^1.3` names
  only a kernel that can read the manifest.

### Added

- **It brings its section of the privacy policy.** The feature has an install
  unit now, which carries nothing but the section `stats` (a count per page and
  day and the referrer's host; no cookie, no address, no personal data) for the
  type `privacy` of Nino 1.4's Legal module - add-only, and a Nino without the
  module ignores the file. It is a starting point, not legal advice - see
  "Legal" in Nino's `docs/development.md`.

### Changed

- **The page titles are read from `/text`.** Where the Text panel writes
  them, whatever `/nino/locales/textfiles` in config.php said - Nino reads
  that key nowhere any more and drops it in 2.0.

- **The README promised a number of visits the feature never had.** A
  paragraph under "What is *not* counted" offered visits - views with an
  external or empty referrer - as a second number shown in the panel.
  Nothing counts them: the store holds the views per page and day and the
  referrer hosts, and the panel shows those. The paragraph is gone.

- **`maxUris` and the retention are read as the kernel hands them.** A
  `max( 1, … )` and a `< 1` guard stood behind settings the kernel already holds
  to the manifest's 50..5000 and 1..60. They are gone; nothing changes for a
  project, and `nino` stays `^1.3`.

- **The page titles it reads follow Nino 1.4's text keys.** A page's title in
  the panel is `/_nino/webpage<uri>/title` (`/webpage<uri>/title` before). Nino
  1.4.0 renames the keys the feature reads and it is renamed with them, so
  `nino` is `^1.4`: a Nino before 1.4 has neither the words nor the keys, and is
  not offered the feature. Nothing has been published under the old keys, so
  there is no migration; a project that already has texts under them copies the
  values to the new keys.

- **The pages table shows the page's title.** A column for the title - the
  `/webpage<uri>/title` text of the page, in the language of the route that
  answers it and else the native one - and one for the path in a muted
  column beside it, instead of the path alone. Without a title the path is
  the name, and the overflow bucket `/…` reads „Other pages“ / „Weitere
  Seiten“. `stats/month` answers `title` with every page row.

- **The README put the panel and its permission in the Content group.** A
  panel a feature brings lands under Features whatever `nav()` names -
  `\Nino\Admin\Panels` overrides the group, so that granting it stays a
  bounded grant - and this panel names `content`, which the README repeated
  for the navigation and again for the roles tab that offers the permission.
  What follows from it was right all along: the **Editor** role is built from
  the Content panels alone, so it does not hold this one. Words only - the
  code is unchanged.

### Fixed

- **robots.txt, sitemap.xml and a json endpoint were counted as pages.** Any
  `GET` answered with `200` counted, so a crawler fetching `/robots.txt` every
  day topped the pages. Only HTML pages count now: a response whose route
  declares a `Content-Type` other than `text/html` is a file, and so is an
  address ending in `.txt`, `.xml`, `.json`, `.rss`, `.atom` or `.webmanifest`
  on a route that declares none. Not any extension - `/v1.2-release-notes` is
  a page. The kernel's own `robots.txt`, `sitemap.xml` and `llms.txt` routes
  and this catalogue's SEO feature declare their type already. A json
  endpoint of your own declares its `Content-Type` on its route
  (`'header' => [ 'Content-Type' => 'application/json' ]`), or it counts as a
  page.

- **The months already counted showed the file hits, and the tile with them.**
  Nothing is deleted: the files hold what they held. The panel and the
  Dashboard tile read through the same rule now (`Stats::isPage()`, the route
  resolved the way the request was), so a month counted before this fix shows
  its pages only - each day's total is the sum of the pages left, a day that
  had nothing else is not listed, and the summary counts what is. The
  referrers of a dropped day go with it; on a day that had files and pages
  both they are as stored, since a referrer is kept per day and not per page.

- **The summary said "1 views" and "1 days with data".** The singular has its
  own word now: "1 view · 1 day with data", „1 Aufruf · 1 Tag mit Daten“.

- **The referrers table said "No views recorded yet".** It has a text of its
  own: no referrers recorded yet.

- **The bar row had no y axis at all.** The busiest day's count is written at
  the top of the row now, where its bar reaches. A label and not a scale: the
  heights stay a share of that number, so one view is still a full bar.

- **"Keep for 13 months" kept fourteen month files.** The retention sweep took
  its cutoff from the first day of the month `retentionMonths` back, which
  leaves that whole month on disk as well as the twelve after it and the
  current one. The setting says "how many monthly files to keep", so the
  cutoff is the first day of the month `retentionMonths - 1` back now: at 13
  it keeps this month and the twelve before it, and the oldest file goes one
  month earlier than it used to. A site that has been counting for longer than
  the retention loses one extra month file the first time a new day is counted
  after the update.

## 1.0.0 — 2026-09-08

- First release: page-view counting with no personal data whatsoever - no
  cookie, no ip address, no fingerprint, nothing stored per visitor. Counts
  a qualifying `GET` `200` at `/nino/http/response` priority 8, one below
  `Modules\Cache`'s priority 9, so a cache hit still counts.
- Per-day totals, per-uri and per-referrer-host (own host and empty
  dropped) breakdowns, one file per month under `/data/stats/`, written
  through `\Nino\Filesystem::mutate()`.
- Settings: `countSignedIn`, `exclude` (lines, exact or `/path/*`), `maxUris`
  (the per-day distinct-uri budget, folding the rest into `'/…'`) and
  `retentionMonths` (checked on the first count of a new day).
- A **Stats** panel in the workbench's Content group: a month selector, a
  plain-css bar per day and the two top-50 tables (pages, referrer hosts),
  read-only. A Dashboard tile with the last 7 days' views.

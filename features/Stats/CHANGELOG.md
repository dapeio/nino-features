# Changelog

All notable changes to the Stats feature are documented in this file.
A release is the tag `stats-<version>` of dapeio/nino-features.

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

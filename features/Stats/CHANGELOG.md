# Changelog

All notable changes to the Stats feature are documented in this file.
A release is the tag `stats-<version>` of dapeio/nino-features.

## Unreleased

- Needs Nino `^1.3`, where the constraint said `^1.1`. The sectioned `manual`
  map this manifest carries is only read by a kernel newer than the
  `v1.2.0-beta` tag - `Features::manifest()` refuses it on the tagged one -
  and `^1.1` is satisfied by that kernel, so the catalogue offered the
  feature to an installation that could not then install it. `^1.3` names
  only a kernel that can read the manifest.

### Changed

- **The README put the panel and its permission in the Content group.** A
  panel a feature brings lands under Features whatever `nav()` names -
  `\Nino\Admin\Panels` overrides the group, so that granting it stays a
  bounded grant - and this panel names `content`, which the README repeated
  for the navigation and again for the roles tab that offers the permission.
  What follows from it was right all along: the **Editor** role is built from
  the Content panels alone, so it does not hold this one. Words only - the
  code is unchanged.

### Fixed

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

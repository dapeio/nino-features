# Changelog

All notable changes to the Forms feature are documented in this file. The
format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), the
versions [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## Unreleased

### Fixed

- **Behind a reverse proxy the rate limit counted every visitor as one.**
  Both halves of the per-address cap - the counter written after an accepted
  submission and the check in front of the next one - asked
  `\Nino\Http::getClientIp()` without the app data it needs to resolve an
  address, so behind a proxy they shared one bucket and four submissions by
  anyone turned the form off for everybody. Both pass `$appData` now, so a
  project that named its proxies under `/nino/http/proxies` counts the
  visitor behind them. Needs the kernel change that added the key.

## 1.0.0 — 2026-09-08

First release.

- `[form]` and `[form key="…"]`, drawing a form from its definition under
  `/nino/form/forms` - the markup the shared `.nino-form` script drives, with
  labels resolved through the text system.
- The **Forms** panel: the forms of `config.php` as a list, one form's fields
  on a screen of its own, and the two keys that decide how long submissions
  are kept and whether they are kept at all.
- Three spam guards on the kernel's own route callback at priority 1: the
  moment a form was drawn, a blocklist over every value, and an hourly
  allowance per client address that only accepted submissions spend.

It extends Nino's form endpoint rather than replacing it: `\Nino\Form` stays
the engine and `\Nino\Modules\Form` keeps the route, so a project that
switches this feature off keeps every form it defined, every submission it
recorded, and its Submissions panel.

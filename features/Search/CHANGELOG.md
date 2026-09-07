# Changelog

All notable changes to the Search feature are documented in this file.
A release is the tag `search-<version>` of dapeio/nino-features.

## 1.0.0 — 2026-09-07

- Moved unchanged from dapeio/nino 1.0.0-beta, where it lived under
  `app/Nino/Modules/Search/` and then `features/Search/`: the locale-aware
  fuzzy index over `/nino/elements/index`, the rebuild after every committed
  Elements write, the Search panel with its one button, and
  `tests/search-smoke.php`.
- Versioned here from now on, with its own `version` in `feature.php`,
  this changelog and the `nino` constraint `^1.0`.

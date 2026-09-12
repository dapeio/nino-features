# Changelog

All notable changes to the Hello World feature are documented in this file.
A release is the tag `hello-<version>` of dapeio/nino-features.

## 1.0.0 — 2026-09-12

First release.

- A complete feature that does one small thing, written to be copied: a
  shortcode, a route, a panel, a setting, an install unit, text fills, an asset,
  stored data, an upgrade hook and a test — each exactly once.
- `[hello]` and `[hello name="Ada"]` greet; `/hello` is a page of its own,
  installed with the feature.
- The greeting is a setting the Features panel draws by itself; the name is
  stored by the feature's own panel. The README says where the line between the
  two runs, which is the one decision the example exists to make.
- `tests/hello-smoke.php` is 45 checks, laid out in the order a request meets
  the parts.

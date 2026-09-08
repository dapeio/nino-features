# Changelog

All notable changes to the Consent feature are documented in this file.
A release is the tag `consent-<version>` of dapeio/nino-features.

## 1.0.0 — 2026-09-08

- First release: a cookie/consent banner without a third party -
  `necessary`/`statistics`/`marketing`/`external` categories, the settings
  saying which of the optional three a site uses, `[consent]` and
  `[consent-settings]`.
- `consent.js` reads and writes the one consent cookie, activates
  `<script type="text/plain" data-consent="...">` placeholders once a
  category is allowed, toggles `data-consent-show`/`data-consent-hide`, and
  dispatches `nino:consent` - no dependencies, no build step.
- `\Nino\Modules\Consent::allowed()` for a server-side, read-only check of
  the same cookie.
- `consent.css`/`consent.js` ship through the project's own `/.cache/style.css`/
  `/.cache/script.js` bundles (`\Nino\Html::addAsset()`), the same mechanism
  the kernel uses for `Nino.css`/`Nino.js`.
- English and German banner texts via the install unit, add-only into the
  project's `text/<locale>.php`.

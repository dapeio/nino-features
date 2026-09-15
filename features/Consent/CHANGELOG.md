# Changelog

All notable changes to the Consent feature are documented in this file.
A release is the tag `consent-<version>` of dapeio/nino-features.

## Unreleased

### Fixed

- **A cookie anybody can send was a 500 on every page.** `allowed()` cast the
  consent cookie to a string, and a request sending `nino_consent[]=x` makes
  that cast an "Array to string conversion" - a level the kernel treats as
  fatal. Every page that asks whether something is allowed answered 500 for
  as long as the cookie was sent, which is as long as the sender likes. The
  cookie is read as a string or not at all now, and so is the cookie name
  from the settings: a hand-edited `config.php` is no more typed than a
  request is.

- **README.md advised hiding an iframe, which does not stop it.** The example
  for `data-consent-show` put a map iframe inside a `hidden` container, and a
  hidden iframe is fetched exactly like a visible one - measured in Chromium for
  the `hidden` attribute, `display:none` and `visibility:hidden` alike. The
  visitor's address reached the provider before the banner was answered, which
  is the one thing this feature is for. The example now toggles a notice rather
  than a frame, and a new section says plainly that the pattern is not for an
  iframe and names the two that work: the `<script type="text/plain">`
  placeholder this feature already has, and the External Embeds feature.

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

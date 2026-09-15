# Changelog

All notable changes to the Newsletter feature are documented in this file.
A release is the tag `newsletter-<version>` of dapeio/nino-features.

## Unreleased

### Fixed

- **A post or a link whose value is an array was a 500.** `email[]=x` on the
  signup, and `?confirm[]=x` or `?unsubscribe[]=x` on the confirm page,
  reached a `(string)` cast; the "Array to string conversion" warning that
  raises is a level the kernel treats as fatal, so an address anybody can
  visit answered 500. The signup reads its two values as strings or not at
  all (an array in the honeypot reads as a filled honeypot, which is what it
  is), and a token that is not a string is no token - the "invalid" page, the
  same as a token that does not match.

- **The address is cut on a character boundary.** The length cap used
  `substr()`, which splits a multibyte character when the cut lands inside
  one. An address that long is refused either way; what changes is that the
  value the refusal looks at is still text.

## 1.0.0 — 2026-09-07

- Moved unchanged from dapeio/nino 1.0.0-beta, where it lived under
  `app/Nino/Modules/Newsletter/` and then `features/Newsletter/`: the
  double opt-in signup under `/.newsletter`, the confirm and unsubscribe
  links, the removal record with the restore merge, and the Newsletter
  panel.
- Versioned here from now on, with its own `version` in `feature.php`,
  this changelog and the `nino` constraint `^1.0`.

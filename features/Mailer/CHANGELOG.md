# Changelog

All notable changes to the Mailer feature are documented in this file.
A release is the tag `mailer-<version>` of dapeio/nino-features.

## Unreleased

### Changed

- **The transport reads its settings in one go.** `Features::setting()`
  answers one name by building every value the manifest declares - it reads
  the feature, then validates each stored value against its schema - and this
  transport asked nine times per mail. Measured at 0.0104 ms against 0.0012,
  which is nothing beside an SMTP round trip; nine reads of one thing is nine
  places for the ninth to be forgotten, which is the reason.

## 1.0.0 — 2026-09-08

- First release: a pure-php SMTP transport registered under
  `\Nino\Mail::TRANSPORT`, so every mail `\Nino\Mail::send()` hands out -
  the contact form, the Newsletter feature, a project's own code - goes out
  over SMTP instead of the server's `mail()`.
- Settings for host, port, encryption (STARTTLS, TLS from the start, or
  none), username, password, the From address and name, a timeout and
  certificate verification.
- A **Mailer** panel in the workbench's System group: a status line and a
  **Send test mail** button, going through the same transport and the same
  per-ip cap as every other mail on the site.

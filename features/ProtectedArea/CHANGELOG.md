# Changelog

All notable changes to the Protected area feature are documented in this file.
A release is the tag `protected-<version>` of dapeio/nino-features.

## 1.0.0 — 2026-09-08

- First release: one shared password behind which one or more configured
  uri prefixes (and everything below them) sit, without accounts or any
  per-visitor tracking - a gate at priority 1 on `/nino/http/response`
  replaces a locked visitor's response with a password form (`401`,
  `Cache-Control: no-store`) before `Modules\Cache` or the render ever see
  it.
- Settings for the protected paths (`lines`), the shared password
  (`secret`, empty means the feature is inert) and a per-ip, per-hour
  attempt cap (`int`, default 5) before the form refuses further tries -
  right password included - for the rest of that hour.
- `POST /.protected` checks the password (CSRF-checked, `hash_equals()`)
  and unlocks the session on success, redirecting to a posted `return`
  resolved to a local path only; `GET /.protected/logout` locks it again.
- The shortcode `[protected-logout]` renders a lock-again link only while
  the current session is unlocked, so a footer can carry it unconditionally.
- The configured prefixes are also added to the full-page cache's
  blacklist at runtime, so an unlocked visitor's page is never stored and
  served back to a locked one.

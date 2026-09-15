# Changelog

All notable changes to the Protected area feature are documented in this file.
A release is the tag `protected-<version>` of dapeio/nino-features.

## Unreleased

### Fixed

- **The posted return path was rendered, not just carried.** The
  wrong-password answer puts what was posted as `return` back into the form's
  hidden field, so the visitor keeps their destination - escaped, but the page
  it lands in is rendered afterwards, and the fill and shortcode pass read the
  value along with everything else. A locked visitor could put any of the
  project's templates, and any of its texts, into the 401 they were served,
  by posting `[template /templates/page-whatever]`. The `[` is swapped for
  `&#91;` now, the way `\Nino\Modules\Elements` has always done it for editor
  content.

- **A post whose values are arrays was a 500.** `return[]=x` reached a
  `(string)` cast, and the "Array to string conversion" warning that raises is
  a level the kernel treats as fatal - an unauthenticated 500 from a post
  anybody can send. Both values are read as strings or not at all, the same
  reading `\Nino\Form::posted()` gives a submission.

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

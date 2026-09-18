# Changelog

All notable changes to the Protected area feature are documented in this file.
A release is the tag `protected-<version>` of dapeio/nino-features.

## Unreleased

- Needs Nino `^1.3`, where the constraint said `^1.1`. The sectioned `manual`
  map this manifest carries is only read by a kernel newer than the
  `v1.2.0-beta` tag - `Features::manifest()` refuses it on the tagged one -
  and `^1.1` is satisfied by that kernel, so the catalogue offered the
  feature to an installation that could not then install it. `^1.3` names
  only a kernel that can read the manifest.

### Fixed

- **An invalid byte in a value rendered as nothing.** `htmlspecialchars()`
  answers input that is not valid UTF-8 with `''` unless `ENT_SUBSTITUTE` is
  among its flags, and every call here spelled the flags out without it.
  Every call carries it now, as the kernel's do, and
  `tests/escaping-smoke.php` reads every feature for the next one.

- **A burst of parallel posts walked straight through the attempt cap.** The
  count was read without a lock, the password was compared, and only a wrong
  one was written back afterwards. Every request that arrived between that
  read and that write saw the same count, passed the same check and got to
  try a password, so `attempts` was a cap per burst rather than per hour -
  and a burst is the one case a cap is for. Measured with eight parallel
  posts against a cap of three: eight tries taken, eight on file. The attempt
  is claimed first now, inside the lock that records it, so a request either
  holds a try or is refused; the same eight posts take three.

  That makes the correct password spend a try as well - it has to, or the
  claim would be back after the comparison - so a successful unlock drops the
  ip's counter again. `attempts` stays what the setting says: wrong passwords
  per visitor and hour.

- **Behind a reverse proxy the cap locked out everybody at once.** The
  attempt counter is keyed by the client address, and that address came from
  `\Nino\Http::getClientIp()` without the app data it needs to resolve one:
  behind a proxy every visitor was the proxy, so three wrong tries by anyone
  closed the area for all of them, for the hour. The call passes `$appData`
  now, so a project that named its proxies under `/nino/http/proxies` counts
  the visitor behind them. Needs the kernel change that added the key.

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

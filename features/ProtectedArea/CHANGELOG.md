# Changelog

All notable changes to the Protected area feature are documented in this file.
A release is the tag `protected-<version>` of dapeio/nino-features.

## Unreleased

- **The panel uses the workbench's request helper and its wording of a failure,
  where the Nino has them.** Every request goes through `Nino.adminUi.api`,
  which signs the page in again over what is on screen when the session has
  ended instead of losing it. The password and the pages keep their own Save and
  their own line. On a Nino without the helper (1.3.x) the panel posts as it
  did, but from the project's own directory: it posted to `/_admin/` from the
  root of the domain, which a project in a subdirectory does not answer. `nino`
  stays `^1.3`.

- Needs Nino `^1.3`, where the constraint said `^1.1`. The sectioned `manual`
  map this manifest carries is only read by a kernel newer than the
  `v1.2.0-beta` tag - `Features::manifest()` refuses it on the tagged one -
  and `^1.1` is satisfied by that kernel, so the catalogue offered the
  feature to an installation that could not then install it. `^1.3` names
  only a kernel that can read the manifest.

### Added

- **It brings its section of the privacy policy.** The install unit adds the
  section `protected` (the session cookie, the attempts counted per address and
  hour, the legal bases) to the type `privacy` of Nino 1.4's Legal module -
  add-only, and a Nino without the module ignores the file. It is a starting
  point, not legal advice - see "Legal" in Nino's `docs/development.md`.

- **A panel for the password and the pages, with its own permission.**
  **Protected area** in the Features group (`/_admin/protected/manage`, offered
  on the Users panel's roles tab) sets a new password - typed twice, 8 to 200
  characters, never shown again, never in the activity log - lists the site's
  pages to tick the protected ones off, and signs everybody out. The page list
  is the persisted `GET` routes that are pages: no front page, no `/_`, `/.`,
  error pages or files; the language variants of a page are one row and one
  tick; a wildcard route is listed as its prefix. Saving replaces what the
  list can name and keeps every other line of `paths` as the developer wrote
  it, and the server holds every posted path to the list it builds itself.
  Choosing nothing, which switches the protection of the listed pages off, is
  asked about first. The settings stay the Features panel's too. The manifest
  names the panel and the new callback in its `manual`.

- **A new password, and a button, sign everybody out.** An unlock carried a
  bare `true` in the session, so a member who left kept the area until the
  browser's session ended. It carries a session epoch now,
  `/data/protected-session.php` (listed under `data`, so a backup carries it),
  and `ProtectedArea::signOutAll()` writes a new one: every session that
  unlocked before it is locked again. Changing the password in the panel does
  that, and so does the button. A session that holds the old `true` stays
  valid until the first sign-out, so an update asks nobody for the password
  again. A `password` changed in the Features panel rotates the epoch as well
  (see Fixed below).

- **The protected pages stay out of the sitemap.** Where the SEO feature is
  installed it asks `/seo/exclude`, and this feature answers with every
  protected prefix and its subtree while a password is set - so `sitemap.xml`
  and `llms.txt` no longer list a page the visitor cannot open, with its
  title. Nothing is added to `requires`, and without a password nothing is
  excluded.

### Changed

- **The text keys follow Nino's grammar, and the feature reads Nino 1.4's.**
  `/protected/title`, `text`, `label/password` and `label/submit` are
  `/template/page-protected/intro/title`, `…/intro/text`, `…/form/password` and
  `…/form/submit` (the words of `page-protected.tpl`); `/protected/error/wrong`
  and `locked` are `/feature/protected/error/wrong` and `locked`,
  `/protected/label/logout` is `/feature/protected/logout/label`, and the fill
  the gate sets, `/protected/return`, is `/feature/protected/form/return`, still
  blacklisted. The panel names a page by `/_nino/webpage<uri>/title`. Nino 1.4.0
  renames the keys the feature reads and it is renamed with them, so `nino` is
  `^1.4`: a Nino before 1.4 has neither the words nor the keys, and is not
  offered the feature. Nothing has been published under the old keys, so there
  is no migration; a project that already has texts under them copies the values
  to the new keys.

- **The German install texts say „Du“.** `/protected/text`,
  `/protected/error/wrong` and `/protected/error/locked` read „Bitte gib das
  Passwort ein, um fortzufahren.“, „Bitte versuche es erneut.“ and „Bitte
  versuche es in einer Stunde erneut.“ now. The install unit is applied
  add-only: a project that already activated the feature keeps its wording,
  activating it again does not replace it, and the three keys can be edited in
  the Text panel. English is unchanged.

- **The README listed a `text/` this feature does not have and named a number
  no setting does.** The directory holds `templates/` and `tests/` and no
  `text/` at all - the words its own two templates carry are the project's,
  written by the install unit - and the listing said the other way round. The
  reverse-proxy note also had the area locked "as soon as anyone has tried
  three times", where the cap is the `attempts` setting, five by default
  (`ProtectedArea::DEFAULT_ATTEMPTS`), and the settings table three screens
  above says so. It names the allowance rather than a number now. Words only -
  the code is unchanged.

### Fixed

- **A password changed in the Features panel signed nobody out.** It is a
  settings form and rotated nothing, so a member who left kept the session the
  browser held. The feature listens on `/nino/admin/action` now and rotates
  the epoch for a `features/settings` of `protected` that sent a password. A
  secret sent empty keeps the stored one and the kernel may blank it in the
  event, so the epoch file records a keyed hash of the password it was written
  under (`pw`, never the password) and a save that left it signs nobody out; an
  epoch from before that has nothing to compare with and is rotated by the first
  such save, once. The README sentence that said it does not is corrected.

- **A password changed on the feature's own screen could leave a session of
  the old one.** The sessions were locked out before the password was written
  only; somebody unlocking with the old password in between was stamped with the
  new epoch. They are locked out again after a successful write.

- **The panel showed the pages as of the first time it was opened.** Opening it
  again asks for the state again, and draws it unless something typed or
  ticked is waiting to be saved (a failed answer leaves the screen alone).

- **A password's length was counted in bytes on the server and in UTF-16 units
  on the screen.** Seven characters with umlauts passed the floor, a hundred and
  fifty failed the ceiling. Both count characters now.

- **With javascript on, nobody got through the password form.** The form
  carried `class="nino-form"`, which the kernel's script binds to the
  contact-form request: it prevents the native submit, posts the password by
  XHR and writes the answer into the form's first `<p>` - this one has none,
  so the handler stopped with a TypeError. A right password did not take the
  visitor to the page (reloading did) and a wrong one showed nothing. The
  class is gone from `install/templates/page-protected.tpl`, and the form is a
  plain form again. An install unit is applied add-only and never again, so
  the fix reaches new activations only: a project that activated the feature
  earlier removes ` class="nino-form"` from the `<form>` tag of its own
  `templates/page-protected.tpl` by hand.

- **The Text panel reported `/protected/return` as a missing key.** The
  password form's template carries it, the gate fills it at request time, and
  no text file ever answers it - so the panel's scan for missing keys listed
  it on every project with this feature, and the Dashboard counted it, as a
  gap nobody could close. The install unit lists the key under `blacklist`
  now, which is what the scan skips. A project that activated the feature
  before this entry ignores the key once in Text → Keys (the same entry), or
  activates the feature again; the suite holds that the scan reports no key
  of this feature.

- **The form, the logout link and both redirects left a site that sits in
  a subdirectory.** They started at the domain root - `action="/.protected"`,
  `href="/.protected/logout"`, a redirect to the posted `return` as it came
  and one to `/` - so a site at `/shop` posted its password beside itself,
  linked its logout into a 404 and was left for the domain's front page on
  locking again. Every address carries the project directory now:
  `[[/nino/dir]]` in the two templates, the `/nino/dir` key in front of both
  redirects, the way the kernel's own templates and the Forms and Redirects
  features write theirs.

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

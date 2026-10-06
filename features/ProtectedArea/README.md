# Protected area

**Key:** `protected` · **Class:** `\Nino\Modules\ProtectedArea` · **Version:** 1.0.0 · **Nino:** `^1.4`

Puts one or more pages behind one shared password - a members' area, a
client preview, an internal page - without accounts. A visitor who opens a
protected uri sees a password form instead of the page; after the right
password, the session is unlocked and they see the protected pages until
they lock the area again or the session ends. There is no account, no user
list, and no per-visitor tracking - just one password and one session flag.

One directory, the shape the [feature recipe](https://github.com/dapeio/nino/blob/main/docs/recipes/feature.md)
describes: `feature.php`, `ProtectedArea.php`, `Admin/` with `assets/` and
`text/` for the workbench panel, `install/`, `templates/`, `tests/`. The words
the panel carries are in `text/`; the words of the password form and of the
two small templates are the project's, written by the install unit. The
changes per version are in [CHANGELOG.md](CHANGELOG.md).

## The panel

**Protected area** in the Features group of the workbench rail, behind its own
permission, `/_admin/protected/manage` - the Users panel's roles tab offers it
under Features. It does three things, and only those; the settings
themselves stay the Features panel's, and both read and write the same
two (`paths`, `password`):

- **Password.** A new password, typed twice and at least 8 characters (the
  floor the kernel holds an account to; 200 at most). It is saved as the
  `password` setting, posted as `pw`, and never shown again - the screen says
  that one is set, nothing more. **A new password signs everybody out**, see
  [Signing everybody out](#signing-everybody-out), so a member who left loses
  access with it. The Features panel's own form for `password` does not do
  that - it is a settings form and rotates nothing - so a password changed
  there is followed by the panel's **Sign everybody out**.
- **Pages.** The site's pages as a list to tick off, instead of typed uris.
  The list is the persisted `GET` routes of `config.php` that a visitor opens
  in a browser: the front page is not in it (the gate skips an empty prefix, so
  ticking it would protect nothing), nor are `/_...` and `/...` paths, error
  pages (a `statusCode` of 400 or more), anything that declares a
  `Content-Type` other than `text/html` - robots.txt, sitemap.xml, a json
  endpoint - and a route that is not `GET`. A wildcard route such as
  `GET://blog/*` is listed as the prefix it stands for, `/blog`. The locale
  variants of one page - the routes that share an internal `uri` - are one
  row, and one tick protects every language; the title is that page's
  `/_nino/webpage<uri>/title` text in the route's locale, else the native one, else
  the path. A row whose address already lies below a protected prefix reads
  "through a wider path" and cannot be changed. Saving replaces what the list
  can name and **keeps every other line of `paths`** as the developer wrote
  it, ahead of the choice - a prefix of a wildcard feature's records, or one
  with no route at all, stays what it was and is shown under the list. The
  server holds every posted path to the list it builds itself, in full: an
  address it does not list is a `400`. Choosing nothing switches the protection
  of the listed pages off, and the screen asks first.
- **Sign out.** One button, asked about first, that locks every session.

Actions: `protected/state`, `protected/pages`, `protected/password` and
`protected/signout`, each guarded with the panel's permission. The activity
log says that the password changed, never what it is. The workbench announces
every dispatched action, the posted data included, on `/nino/admin/action`:
on Nino 1.3 a listener of that event is handed `pw` the way it is handed an
account's password on the Users panel.

## Settings

The Features panel's form for `protected`:

| Setting | Type | Default | Meaning |
| --- | --- | --- | --- |
| `paths` | lines | *(empty)* | One uri prefix per line, eg. `/intern` - protects that page and everything below it (the panel's page list writes this setting). A line has to start with `/` and must not start with `/_` (Nino's own tools) or `/.` (module endpoints, this feature's own `/.protected` included); such a line is silently ignored rather than protecting nothing by accident. A trailing slash is stripped. |
| `password` | secret | *(empty)* | The one password every visitor uses. **Empty means nothing is protected** - the feature stays completely inert, whatever `paths` says, until this is set. |
| `attempts` | int, 1-50 | 5 | Wrong passwords allowed per visitor and hour before the form refuses for the rest of that hour, right password included (see [The attempt cap](#the-attempt-cap)). |

## The gate

`init()` registers `callbackGate()` on `/nino/http/response` at priority 1 -
before `Modules\Cache`'s own callback (priority 9) ever decides whether a
response may be cached, and before the render turns a route's body into
html. For every request whose uri (`Http::getRequest()`'s
`/nino/http/request`/`uri`, not the route's own resolved uri) lies under a
configured prefix and whose session does not hold `unlocked`, it replaces
the response outright:

- `body` becomes `[template /templates/page-protected]`, the password form
  the install unit copies;
- `statusCode` becomes `401` - never a `200`: nothing was actually served,
  and a `401` is honest about that and is never something the full-page
  cache would consider storing in the first place;
- the header `Cache-Control: no-store` is added.

This runs for **every** request under a protected prefix, whatever route
would otherwise have answered it - including one that does not exist. A
guessed sub-path under `/intern` gets the same password form a real page
would, not a `404` that would confirm or deny it exists.

**A signed-in workbench user is not exempt.** The password is the only way
in, on purpose: a preview left open while an editor is signed in to
`/_admin` for something unrelated must not quietly become visible to
everyone else too. If workbench accounts should double as access to a
protected page, that is a different feature - see
[What this is not](#what-this-is-not).

## Cache exclusion

An unlocked visitor's `200` response is an ordinary page as far as
`Modules\Cache` can tell, and the full-page cache would happily store and
then serve it to the very next visitor - locked or not. To prevent that,
`init()` also appends every configured prefix as `<prefix>` and `<prefix>/*`
to `$appData['/nino/cache/blacklist']` **at runtime only** - never written
to `config.php` - for as long as a password is actually configured. A
`paths` change therefore takes effect on the next request; nothing stale is
left behind. While the feature is inert (no password), nothing is excluded,
because nothing needs to be.

## Unlocking

`init()` registers the route `POST://.protected` and its handler,
`callbackUnlock()`, the way `Modules\Form` registers `POST://.form`: it
checks the kernel's CSRF state (`./nino/csrf/blocked`, set by the required
`\Nino\Csrf` before this callback ever runs - the form has to render
`[csrf]`), reads `password` and `return` from the posted form, and:

1. claims one attempt against the cap for this client ip, and refuses with
   `429` where the cap for the current hour is spent - see
   [The attempt cap](#the-attempt-cap) - without even looking at the
   password;
2. compares the posted password against the configured one with
   `hash_equals()` and, on a match, drops the ip's counter, stamps the session
   with the current epoch (`\Nino\Runtime::setSessionValue( $appData,
   './protected/unlocked', <epoch> )`, see [Signing everybody
   out](#signing-everybody-out)) and answers `303` to `return`, resolved to a **local path only** - one
   starting with a single `/`, no `//`, no scheme. Anything else (a full
   url, a protocol-relative one, `/javascript:...`) becomes `/` instead;
3. otherwise re-renders the password form with `statusCode` `401` and the
   wrong-password error shown, so the visitor sees what went wrong instead
   of being redirected away from it. The attempt is already counted.

The password never appears in a log line or in any response, on success or
on failure.

### The attempt cap

Every attempt is counted per client ip in a fixed one-hour window, in
`/data/protected.php` - the same idea as `\Nino\Mail::_hit()`'s per-ip send
cap (copied, not called: that counter is mail's own). Once `attempts` tries
have been counted for an ip in the current window, every further attempt
from it is answered `429` with the "too many attempts" error, **even a
correct password** - a leaked or guessed password cannot be brute forced
past a prefix nobody has found yet, either. The window resets an hour after
the first attempt in it; a stale window is dropped the next time anything
writes to the file, so it never grows without bound.

The count is claimed before the password is compared, and inside the lock
that writes it, so a burst of parallel posts cannot all read the same count
and all pass the same check. That also means the correct password spends a
try - so a successful unlock drops the ip's counter again, and `attempts`
stays what the setting says it is: wrong passwords per visitor and hour.

Which address that is, is the kernel's answer: behind a reverse proxy it is
the proxy for every visitor alike unless the proxy is named under
`/nino/http/proxies`, and without that the first visitor to spend the hour's
allowance locks the area for everybody. See the Config panel's **Reverse
proxies in front of this site**.

## Signing everybody out

An unlock is the session's `unlocked` flag, and its value is the **session
epoch** the unlock happened under: a string in `/data/protected-session.php`,
`{ epoch }`. `\Nino\Modules\ProtectedArea::signOutAll()` writes a new one
(`bin2hex( random_bytes( 16 ) )`, through `Filesystem::mutate()`), and from then
on every session that unlocked before it reads locked - the flag it holds is
not the epoch any more. The panel's **Sign everybody out** and every change of
the password end there. Nobody is identified, so there is no one to name; the
epoch is all there is to rotate.

While no epoch has ever been written the flag is a bare `true`, the value this
feature stored before the epoch existed - so an update asks nobody for the
password again - and `unlocked()` accepts it until the first sign-out. A
password change writes the epoch first and the password second, so a write that
fails leaves the old password in place rather than the new one beside sessions
it never asked for.

## Locking again

`GET /.protected/logout` unsets the session's `unlocked` flag and redirects
to the project's front page. A plain `GET` link on purpose, not a form: it discloses nothing a
visitor on a protected page could not already tell, so it needs no CSRF
token to be safe. The shortcode `[protected-logout]` renders

```html
<a href="[[/nino/dir]]/.protected/logout" class="nino-protected-logout">[[/feature/protected/logout/label]]</a>
```

only while the current session actually reads unlocked, and nothing at all
otherwise - so a template's footer can carry `[protected-logout]`
unconditionally and it only ever shows up for a visitor it applies to.

Every address this feature writes carries the project directory in front -
`[[/nino/dir]]` in the two templates, the `/nino/dir` key in both redirects -
so a site installed in a subdirectory posts, links and redirects
within itself. Request uris are the project's own, keyed without that
directory, which is why the `return` a form carries gets it put back in front
on the way out.

## The form

The install unit brings `templates/page-protected.tpl`: a heading, a text,
and `<form method="post" action="[[/nino/dir]]/.protected">` with the password field, a
hidden `return` field carrying the uri that was actually asked for (or, on
a failed unlock, the `return` that was posted), `[csrf]`, and a submit
button. `[protected-error]` renders

```html
<p class="nino-protected-error">[[/feature/protected/error/wrong]]</p>
```

or the same with `locked` in place of `wrong`, only while this very request
actually failed one of those two ways - nothing otherwise. The form carries no
`nino-form` class: the kernel's script binds every `.nino-form` to the
contact-form request, which prevents the native submit, posts by XHR and
writes the answer into the form's first `<p>` - this one has none - so a
visitor with javascript on was never taken to the page and a wrong password
showed nothing. A project that activated the feature before this was fixed has
the old file: the unit is applied add-only and never again, so it removes the
class from `templates/page-protected.tpl` by hand (see the changelog). The words
(`/template/page-protected/intro/title`, `/template/page-protected/intro/text`, `/template/page-protected/form/password`,
`/template/page-protected/form/submit`, `/feature/protected/error/wrong`,
`/feature/protected/error/locked`, `/feature/protected/logout/label`) are ordinary,
editor-maintained texts the install unit writes into the project's
`text/<locale>.php`, merged add-only the way every unit is applied.
`/feature/protected/form/return` is not one of them: the gate fills it at request time,
and the unit lists it under `blacklist` so that the Text panel's scan for
missing keys does not report a key no text file can answer.

## The sitemap

Where the [SEO feature](../Seo/README.md) is installed, the protected pages
stay out of `sitemap.xml` and `llms.txt`: `init()` registers a callback under
the literal string `/seo/exclude` (nothing is added to `requires`, and a
constant of a feature that may not be there would be a fatal error), which
answers every configured prefix as `<prefix>/*` - the page and everything
below it - while a password is set. Without a password nothing is protected
and nothing is excluded. `robots.txt` is not touched: a `Disallow` line would
tell every reader which paths a password guards.

## Helpers

```php
\Nino\Modules\ProtectedArea::protects( array &$appData, string $uri ): bool
\Nino\Modules\ProtectedArea::unlocked( array &$appData ): bool
\Nino\Modules\ProtectedArea::prefixes( array &$appData ): array
\Nino\Modules\ProtectedArea::signOutAll( array &$appData ): bool
```

`protects()` answers whether a uri lies under a configured prefix (`false`
for every uri while the password is empty); `unlocked()` answers whether the
current session has already unlocked, under the current epoch. Both are safe
to call from a template shortcode or a project's own module - the gate itself
uses nothing else. `prefixes()` is the configured `paths` as the gate reads
them - normalised, the invalid ones dropped - and `signOutAll()` writes a new
session epoch, `false` where the file could not be written.

## What this is not

- **Not per-page passwords.** One password protects every configured
  prefix; there is no way to give two protected areas different passwords
  without two copies of the feature under different keys (not supported).
- **Not a user list.** Nobody is identified, nothing records who unlocked
  what or when - a session either has the flag or it does not.
- **Not a replacement for real authentication.** A shared password that
  travels by word of mouth, email or chat is exactly as secret as the least
  careful person it was given to. For anything that needs individual
  accounts, roles, or an audit trail, use the workbench's own accounts
  (`\Nino\Auth`) instead - this feature does not read them, and being
  signed in to `/_admin` does not unlock a protected page either.

## Data

Two files under `data/`, listed under `data` in the manifest so the
workbench's daily backup carries them:

| File | Content |
| --- | --- |
| `/data/protected.php` | the attempt cap's own counter, by client ip: `{ tries, reset }` per ip with an unsuccessful try in the current window |
| `/data/protected-session.php` | the session epoch, `{ epoch }`, written by a sign-out and read by every unlock - see [Signing everybody out](#signing-everybody-out); absent until the first sign-out |

There is nothing to restore-merge here (unlike a subscriber list, an
elapsed rate-limit window is never worth preserving across a restore), so
this feature registers no `/nino/admin/restore` callback. A restore brings the
epoch of the backup back with it, which makes a session that was signed out
after that backup valid again for as long as the browser holds it - sign
everybody out once more after a restore if that matters.

## Tests

`tests/protected-smoke.php` is the feature's own test: the manifest and the
activation through `\Nino\Features` with the unit applied, `protects()`
against configured prefixes and their boundaries, the gate replacing a
locked response and extending the cache blacklist, unlocking with a wrong
and then the right password, an unsafe `return` falling back to `/`, the
per-ip attempt cap - including eight real processes posting at once, since
what a cap has to survive is a burst - locking again and
`[protected-logout]`, the session epoch (a sign-out, a new password, a
session from before the epoch kept), the Seo exclusion, the password form
not being one the contact-form script binds, an empty password leaving the
feature inert, the panel (its guards, the state, the pages - a choice, an
unlisted path, the developer's own prefixes kept, locale variants together,
an empty choice - the password and signing out, none of them ever holding the
password) and deactivation. `tests/protected-js-smoke.js` is the panel
script's own test over a dom stand-in - the list and its states, what a save
posts, the confirmations, the two password entries - and the suite above runs
it where node is on the path. It loads Nino's
`tests/harness.php` from the checkout three levels up - where the feature
sits in a project - or from the one `NINO_ROOT` names:

```bash
php features/ProtectedArea/tests/protected-smoke.php
NINO_ROOT=../nino php features/ProtectedArea/tests/protected-smoke.php
```

With the feature copied into a checkout, Nino's `tests/features-smoke.php`
validates the manifest as well, and PHPStan analyses the class.

## A note on the directory name

The directory is `features/ProtectedArea/`, so the class is
`\Nino\Modules\ProtectedArea`: a feature's class is derived from its
directory, and `Protected` is a reserved php word that cannot name a class.
The feature key is `protected` all the same - that is what the settings,
the log and the catalogue use.

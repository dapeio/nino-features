# Protected area

**Key:** `protected` · **Class:** `\Nino\Modules\ProtectedArea` · **Version:** 1.0.0 · **Nino:** `^1.1`

Puts one or more pages behind one shared password - a members' area, a
client preview, an internal page - without accounts. A visitor who opens a
protected uri sees a password form instead of the page; after the right
password, the session is unlocked and they see the protected pages until
they lock the area again or the session ends. There is no account, no user
list, and no per-visitor tracking - just one password and one session flag.

One directory, the shape the [feature recipe](https://github.com/dapeio/nino/blob/main/docs/recipes/feature.md)
describes: `feature.php`, `ProtectedArea.php`, `install/`, `text/` (none - this
feature has no workbench panel and reads no fills of its own). The changes
per version are in [CHANGELOG.md](CHANGELOG.md).

## Settings

The Features panel's form for `protected`:

| Setting | Type | Default | Meaning |
| --- | --- | --- | --- |
| `paths` | lines | *(empty)* | One uri prefix per line, eg. `/intern` - protects that page and everything below it. A line has to start with `/` and must not start with `/_` (Nino's own tools) or `/.` (module endpoints, this feature's own `/.protected` included); such a line is silently ignored rather than protecting nothing by accident. A trailing slash is stripped. |
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

1. refuses with `429` when the attempt cap for this client ip is already
   spent for the current hour - see [The attempt cap](#the-attempt-cap) -
   without even looking at the password;
2. compares the posted password against the configured one with
   `hash_equals()` and, on a match, calls
   `\Nino\Runtime::setSessionValue( $appData, './protected/unlocked', true )`
   and answers `303` to `return`, resolved to a **local path only** - one
   starting with a single `/`, no `//`, no scheme. Anything else (a full
   url, a protocol-relative one, `/javascript:...`) becomes `/` instead;
3. otherwise records one wrong attempt for this ip and re-renders the
   password form with `statusCode` `401` and the wrong-password error
   shown, so the visitor sees what went wrong instead of being redirected
   away from it.

The password never appears in a log line or in any response, on success or
on failure.

### The attempt cap

Every wrong password is counted per client ip in a fixed one-hour window,
in `/data/protected.php` - the same idea as `\Nino\Mail::_hit()`'s per-ip
send cap (copied, not called: that counter is mail's own). Once `attempts`
wrong tries have been recorded for an ip in the current window, every
further attempt from it is answered `429` with the "too many attempts"
error, **even a correct password** - a leaked or guessed password cannot be
brute forced past a prefix nobody has found yet, either. The window resets
an hour after the first wrong attempt in it; a stale window is dropped the
next time anything writes to the file, so it never grows without bound.

## Locking again

`GET /.protected/logout` unsets the session's `unlocked` flag and redirects
to `/`. A plain `GET` link on purpose, not a form: it discloses nothing a
visitor on a protected page could not already tell, so it needs no CSRF
token to be safe. The shortcode `[protected-logout]` renders

```html
<a href="/.protected/logout" class="nino-protected-logout">[[/protected/label/logout]]</a>
```

only while the current session actually reads unlocked, and nothing at all
otherwise - so a template's footer can carry `[protected-logout]`
unconditionally and it only ever shows up for a visitor it applies to.

## The form

The install unit brings `templates/page-protected.tpl`: a heading, a text,
and `<form method="post" action="/.protected">` with the password field, a
hidden `return` field carrying the uri that was actually asked for (or, on
a failed unlock, the `return` that was posted), `[csrf]`, and a submit
button. `[protected-error]` renders

```html
<p class="nino-protected-error">[[/protected/error/wrong]]</p>
```

or the same with `locked` in place of `wrong`, only while this very request
actually failed one of those two ways - nothing otherwise. The words
(`/protected/title`, `/protected/text`, `/protected/label/password`,
`/protected/label/submit`, `/protected/error/wrong`,
`/protected/error/locked`, `/protected/label/logout`) are ordinary,
editor-maintained texts the install unit writes into the project's
`text/<locale>.php`, merged add-only the way every unit is applied.

## Helpers

```php
\Nino\Modules\ProtectedArea::protects( array &$appData, string $uri ): bool
\Nino\Modules\ProtectedArea::unlocked( array &$appData ): bool
```

`protects()` answers whether a uri lies under a configured prefix (`false`
for every uri while the password is empty); `unlocked()` answers whether the
current session has already unlocked. Both are safe to call from a template
shortcode or a project's own module - the gate itself uses nothing else.

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

One file under `data/`, listed under `data` in the manifest so the
workbench's daily backup carries it:

| File | Content |
| --- | --- |
| `/data/protected.php` | the wrong-attempt cap's own counter, by client ip: `{ tries, reset }` per ip that has failed at least once in the current window |

There is nothing to restore-merge here (unlike a subscriber list, an
elapsed rate-limit window is never worth preserving across a restore), so
this feature registers no `/nino/admin/restore` callback.

## Tests

`tests/protected-smoke.php` is the feature's own test: the manifest and the
activation through `\Nino\Features` with the unit applied, `protects()`
against configured prefixes and their boundaries, the gate replacing a
locked response and extending the cache blacklist, unlocking with a wrong
and then the right password, an unsafe `return` falling back to `/`, the
per-ip attempt cap, locking again and `[protected-logout]`, an empty
password leaving the feature inert, and deactivation. It loads Nino's
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

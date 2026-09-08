# Consent

**Key:** `consent` · **Class:** `\Nino\Modules\Consent` · **Version:** 1.0.0 · **Nino:** `^1.1`

A cookie/consent banner without any third party, and consent-gated scripts:
a site embeds an analytics or map script only after the visitor allowed that
category. Categories are fixed - `necessary` (always on, cannot be
declined), `statistics`, `marketing`, `external` (maps, videos and other
external media) - and the settings say which of the three optional ones a
site uses at all. The choice itself is a cookie the *browser* writes
(`consent.js`); PHP only ever reads it.

One directory, the shape the [feature recipe](https://github.com/dapeio/nino/blob/main/docs/recipes/feature.md)
describes: `feature.php`, `Consent.php`, `assets/`, `install/`, `tests/`.
There is no panel - the Features panel's own settings form is enough - and
no `data` entry in the manifest, because nothing is kept under `data/`: the
consent choice lives in the visitor's browser. The changes per version are
in [CHANGELOG.md](CHANGELOG.md).

## Put it on the site

Two shortcodes, both meant for the project's shared page frame so they are
on every page:

```html
[consent]
```

renders the banner - put it once, near the end of the page (or wherever a
bottom-fixed element belongs in your markup; it is `position: fixed` in
`consent.css` regardless of where the tag sits).

```html
[consent-settings]
```

renders a small text button that reopens the banner - `[[/consent/open]]`,
"Cookie settings" - meant for the footer, next to the imprint/privacy
links.

Both are ordinary shortcodes (`\Nino\Html::addShortcode()`), registered
in `init()` while the feature is active.

## The banner markup

```html
<div class="nino-consent" hidden data-consent-cookie="nino_consent" data-consent-days="180">
	<div class="nino-consent-content">
		<p class="nino-consent-title">...</p>
		<p class="nino-consent-text">... <a href="..." class="nino-consent-link">...</a></p>
		<div class="nino-consent-categories">
			<label class="nino-consent-category">
				<input type="checkbox" data-consent-category="necessary" checked disabled>
				<span class="nino-consent-category-name">...</span>
				<span class="nino-consent-category-hint">...</span>
			</label>
			<!-- one more <label> per category the settings switched on -->
		</div>
	</div>
	<div class="nino-consent-actions">
		<button type="button" class="nino-consent-btn nino-consent-btn--primary" data-consent-action="accept-all">...</button>
		<button type="button" class="nino-consent-btn" data-consent-action="necessary-only">...</button>
		<button type="button" class="nino-consent-btn" data-consent-action="save">...</button>
	</div>
</div>
```

- `hidden` by default - `consent.js` unhides it once it finds no stored
  choice on the page's `load`.
- One `<label class="nino-consent-category">` per category the settings
  enabled; `necessary` always renders first, checked and disabled. A
  category the settings did not switch on is neither shown nor storable -
  its `<label>` simply is not in the markup.
- The privacy link (`<a class="nino-consent-link">`) only renders when the
  `policyUrl` setting is not empty.
- `data-consent-cookie`/`data-consent-days` carry the `cookieName`/`days`
  settings onto the banner itself, because `consent.css`/`consent.js` are
  static assets, never rendered through the fill engine (see
  [Asset bundling](#asset-bundling-and-the-page-cache) below) - this is how
  the script learns a project's own cookie name and lifetime without a
  build step.
- Plain HTML with classes, no inline styles. Every word is a textfill
  (`[[/consent/...]]`) the install unit wrote into the project - see
  [Texts](#texts).

## The settings

| Setting | Type | Default | Meaning |
| --- | --- | --- | --- |
| `statistics` | bool | `false` | show the statistics category and allow it to be stored |
| `marketing` | bool | `false` | show the marketing category and allow it to be stored |
| `external` | bool | `false` | show the external media category (maps, videos, ...) and allow it to be stored |
| `policyUrl` | url | `''` | linked from the banner text; empty renders no link |
| `cookieName` | string | `nino_consent` | the cookie `consent.js` reads and writes; `/^[A-Za-z0-9_-]+$/` |
| `days` | int | `180` | how many days the cookie is kept (1..365) |

Set them in the workbench's Features panel - there is no panel of this
feature's own. `necessary` is not a setting: it is always on and is never
stored as a category the visitor "chose".

## Gating a script

A script that must not run before a category is allowed is shipped as a
`<script type="text/plain">` placeholder - browsers never execute that type,
which is the whole point:

```html
<!-- loaded from elsewhere -->
<script type="text/plain" data-consent="statistics" data-src="https://example-analytics.example/tag.js"></script>

<!-- inline -->
<script type="text/plain" data-consent="statistics">
	console.log( 'statistics allowed' );
</script>
```

Once `statistics` is allowed, `consent.js` clones the placeholder into a
real `<script>` - every attribute but `type` copied over, `data-src`
becoming `src`, or the placeholder's own text content when there is no
`data-src` - and inserts it right after the placeholder. Each placeholder is
activated at most once, on the page's initial load and again whenever the
visitor changes their choice, without a page reload.

Toggle a placeholder box instead of a script with `data-consent-show`/
`data-consent-hide` - a "load the map" placeholder, say:

```html
<div data-consent-hide="external">Loading the map needs your consent for external media. <button class="nino-consent-open" type="button">Cookie settings</button></div>
<div data-consent-show="external" hidden><iframe src="https://maps.example/embed">...</iframe></div>
```

`consent.js` only ever *unhides* a `data-consent-show` element and *hides* a
`data-consent-hide` one for an allowed category - it never reverses either
in the other direction, so give the "not yet allowed" element its own
visible-by-default markup the way the example above does.

## `document.documentElement.dataset.consent` and the `nino:consent` event

On every page `consent.js` runs on (banner or not), it sets
`document.documentElement.dataset.consent` to the comma-joined list of
currently allowed categories (`necessary` always included: `data-consent="necessary,statistics"`
on `<html>`), then dispatches:

```js
document.addEventListener( 'nino:consent', function( event ) {
	console.log( event.detail.allowed ); // eg. [ 'necessary', 'statistics' ]
} );
```

This fires once on load (even with no stored choice yet - `allowed` is then
just `[ 'necessary' ]`) and again every time the visitor accepts, declines
or saves a selection. Code that does not fit the placeholder-script pattern
(a library that needs to be told rather than merely loaded) listens for this
instead.

## The server-side helper

```php
\Nino\Modules\Consent::allowed( array &$appData, string $category ): bool
```

Reads the cookie from the current request (`$_COOKIE`) so a template
callback can decide server-side whether to render something - `necessary`
is always `true`; a category the settings did not switch on for this site
is never allowed, whatever an old cookie might still say; an unknown
category name is refused the same way. **PHP never writes the cookie** -
only `consent.js` does, from the banner's own buttons.

## Texts

Every word in the banner is a textfill the install unit writes into the
project's own `text/en_US.php`/`text/de_DE.php` at activation (add-only - a
key the project already has stays), so editors keep them current in the
Text panel from then on:

| Key | English default | German default |
| --- | --- | --- |
| `[[/consent/title]]` | We use cookies | Wir verwenden Cookies |
| `[[/consent/text]]` | We use cookies and similar technologies ... | Wir verwenden Cookies und ähnliche Technologien ... |
| `[[/consent/policy-label]]` | Privacy policy | Datenschutzerklärung |
| `[[/consent/accept-all]]` | Accept all | Alle akzeptieren |
| `[[/consent/necessary-only]]` | Necessary only | Nur notwendige |
| `[[/consent/save]]` | Save selection | Auswahl speichern |
| `[[/consent/open]]` | Cookie settings | Cookie-Einstellungen |
| `[[/consent/category/<name>]]` | the category's own label | ditto |
| `[[/consent/category/<name>/hint]]` | one-line explanation | ditto |

`<name>` is `necessary`, `statistics`, `marketing` or `external`. A link to
the imprint, or any other markup, can be added straight into
`[[/consent/text]]` from the Text panel - fills are not escaped, so a
project is free to put a second `<a>` there; this feature only ever renders
the one privacy link it has a dedicated setting for.

## Asset bundling and the page cache

`consent.css`/`consent.js` reach the browser the same way the kernel ships
its own `Nino.css`/`Nino.js`/`Nino.ui.js`: `init()` calls
`\Nino\Html::addAsset()` to add them to the project's **own** `/.cache/style.css`
and `/.cache/script.js` bundles - the exact targets the base install's
`html-header.tpl`/`html-footer.tpl` already load on every page via
`[assets /.cache/style.css]`/`[assets /.cache/script.js]` - rather than a
bundle of this feature's own. That is deliberate: `consent.js` then gates
consent-tagged scripts on *every* page the site's frame renders, whether or
not that particular page happens to render `[consent]` itself - the
`document.documentElement.dataset.consent`/`nino:consent` half of the
contract holds regardless.

The asset paths are `/features/Consent/assets/...`: a kernel from the
catalogue release on resolves `/features/...` through `\Nino\Filesystem::path()`
against the features directory, wherever `NINO_FEATURES_DIR` put it. An
older kernel resolves it against the project root, which is the same place
as long as `features/` is not relocated.

The banner's markup is the same for every visitor - no per-visitor state is
rendered server-side, the choice itself is read and written by the browser
- so the kernel's full-page cache stays valid with this feature active. If
`/nino/cache/status` is on, `[consent]` and `[consent-settings]` render
into the cached page exactly like any other shortcode; nothing here needs
excluding from it.

## Relationship to the base install's own cookie banner

The base install (`_admin/install/library/base/templates/html-footer.tpl`)
ships a plain accept/decline `.nino-cookie-banner`, backed by
`Nino.ui.cookieConsent` in `_nino/Nino.ui.js`, writing `'accepted'` or
`'declined'` into a cookie named `nino_consent`. This feature supersedes it:
`consent.js` removes a `.nino-cookie-banner` it finds in the page, so a
visitor never sees two banners, and it reads the old values - `'accepted'`
counts as every category, `'declined'` as the necessary one alone - so a
choice a visitor already made is kept until they change it. Removing the old
block from the project's `templates/html-footer.tpl` is tidier but not
required.

## What it does not do

- No consent logging: the feature does not record *that* a visitor
  consented, only *what* is currently allowed, in the visitor's own cookie.
  A site that needs a durable consent record for its own accountability
  keeps that separately.
- No third-party consent management platform, no external script, no
  network request of its own - `consent.js` only reads/writes one cookie
  and manipulates the current page's DOM.
- No geo-detection, no "only show in the EU" logic - the banner is either
  on a page or it is not; a project that needs that decides it in its own
  template.
- No enforcement of a project's own scripts: a script not wrapped as
  `<script type="text/plain" data-consent="...">` is not gated - the
  placeholder pattern is opt-in, deliberately, since only the project
  knows which of its scripts need it.

## Tests

`tests/consent-smoke.php` is the feature's own test: the manifest and its
six settings, activation with the unit's texts merged add-only (an existing
key survives), the shortcodes registering in `init()`, the real
`\Nino\Html::addAsset()`/`[assets ...]` bundling end to end (the generated
`/.cache/style.css`/`script.js` genuinely carry this feature's files),
`[consent]` rendering only the categories the settings enabled and the
policy link only when `policyUrl` is set, `[consent-settings]`, `allowed()`
reading the configured cookie name and refusing a disabled category or an
unknown one regardless of what an old cookie says, and deactivation leaving
the settings and merged texts in place. It loads Nino's `tests/harness.php`
from the checkout three levels up - where the feature sits in a project -
or from the one `NINO_ROOT` names:

```bash
php features/Consent/tests/consent-smoke.php
NINO_ROOT=../nino php features/Consent/tests/consent-smoke.php
```

`node --check features/Consent/assets/consent.js` and `eslint` (the
checkout's own `eslint.config.mjs`) check the script; PHPStan analyses the
class.

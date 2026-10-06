# Consent

**Key:** `consent` · **Class:** `\Nino\Modules\Consent` · **Version:** 1.0.0 · **Nino:** `^1.3`

A cookie/consent banner without any third party, and consent-gated scripts:
a site embeds an analytics or map script only after the visitor allowed that
category. Categories are fixed - `necessary` (always on, cannot be
declined), `statistics`, `marketing`, `external` (maps, videos and other
external media) - and the settings say which of the three optional ones a
site uses at all. The choice itself is a cookie the *browser* writes
(`consent.js`); PHP only ever reads it.

One directory, the shape the [feature recipe](https://github.com/dapeio/nino/blob/main/docs/recipes/feature.md)
describes: `feature.php`, `Consent.php`, `assets/`, `install/`, `templates/`,
`tests/`. There is no panel - the Features panel's own settings form is
enough - and the manifest's `data` entry is empty, because nothing is kept
under `data/`: the consent choice lives in the visitor's browser. The changes per version are
in [CHANGELOG.md](CHANGELOG.md).

## Put it on the site

**Activating the feature shows nothing.** The banner is a shortcode, and
nothing writes it into a page for you: until `[consent]` is in the project's
page frame - `templates/html-footer.tpl`, say - no visitor ever sees a banner
and no placeholder script is ever released. Add the two shortcodes by hand.

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

renders a small text button that reopens the banner -
`[[/feature/consent/action/open]]`, "Cookie settings" - meant for the
footer, next to the imprint/privacy links. (The privacy policy of Nino 1.4's
Legal module carries one of its own, see [Privacy policy](#privacy-policy).)
On a page that has no banner the button hides itself: it would have nothing
to open.

Both are ordinary shortcodes (`\Nino\Html::addShortcode()`), registered
in `init()` while the feature is active.

## The banner markup

```html
<div class="nino-consent" hidden role="dialog" aria-labelledby="nino-consent-title" aria-describedby="nino-consent-text" tabindex="-1" data-consent-cookie="nino_consent" data-consent-days="180">
	<div class="nino-consent-content">
		<p class="nino-consent-title" id="nino-consent-title">...</p>
		<p class="nino-consent-text" id="nino-consent-text">... <a href="..." class="nino-consent-link">...</a></p>
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
		<button type="button" class="nino-consent-btn" data-consent-action="accept-all">...</button>
		<button type="button" class="nino-consent-btn" data-consent-action="necessary-only">...</button>
		<button type="button" class="nino-consent-btn" data-consent-action="save">...</button>
	</div>
</div>
```

- `hidden` by default - `consent.js` unhides it once the document is ready
  and it finds no stored choice.
- A dialog (`role="dialog"`, named and described by its title and text), not
  a modal: nothing traps the focus in it. Reopened from a `[consent-settings]`
  button it takes the focus, and gives it back to that button once a choice is
  made. `tabindex="-1"` lets it take the focus without joining the tab order.
- The three actions look the same: accepting everything is no easier to press
  than refusing it.
- The checkboxes show what is stored. Reopen the banner with `statistics`
  allowed and its box is checked, and "Save selection" keeps it; after
  "Necessary only" they are all unchecked.
- One `<label class="nino-consent-category">` per category the settings
  enabled; `necessary` always renders first, checked and disabled. A
  category the settings did not switch on is neither shown nor storable -
  its `<label>` simply is not in the markup.
- The privacy link (`<a class="nino-consent-link">`) only renders when there
  is an address: the `policyUrl` setting, or - with the setting empty - the
  privacy policy of Nino 1.4's Legal module, where it is there (see
  [Privacy policy](#privacy-policy)).
- `data-consent-cookie`/`data-consent-days` carry the `cookieName`/`days`
  settings onto the banner itself, because `consent.css`/`consent.js` are
  static assets, never rendered through the fill engine (see
  [Asset bundling](#asset-bundling-and-the-page-cache) below) - this is how
  the script learns a project's own cookie name and lifetime without a
  build step.
- Plain HTML with classes, no inline styles. Every word is a textfill
  (`[[/feature/consent/...]]`) the install unit wrote into the project - see
  [Texts](#texts).

## The settings

| Setting | Type | Default | Meaning |
| --- | --- | --- | --- |
| `statistics` | bool | `false` | show the statistics category and allow it to be stored |
| `marketing` | bool | `false` | show the marketing category and allow it to be stored |
| `external` | bool | `false` | show the external media category (maps, videos, ...) and allow it to be stored |
| `policyUrl` | url | `''` | linked from the banner text; empty: the privacy policy of the Legal module where there is one, otherwise no link |
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

The inline form does not run under the policy Nino ships (see
[CSP](#csp-the-hosts-a-placeholder-loads-from)): the script it creates is an
inline script, which the policy refuses without `'unsafe-inline'` or a nonce
the template cannot get. Put the code in a file and use `data-src`.

Only a category this site offers is ever allowed in the browser: a
`marketing` placeholder stays a placeholder while the `marketing` setting is
off, whatever an old cookie says. (A page that renders no `[consent]` has no
banner to read the offer from; put the banner in the frame so every page
knows it.)

Once `statistics` is allowed, `consent.js` clones the placeholder into a
real `<script>` - every attribute but `type` copied over, `data-src`
becoming `src`, or the placeholder's own text content when there is no
`data-src` - and inserts it right after the placeholder. Each placeholder is
activated at most once, on the page's initial load and again whenever the
visitor changes their choice, without a page reload.

Toggle a box instead of a script with `data-consent-show`/`data-consent-hide` -
a notice, a button, a piece of copy that only applies once something is allowed:

```html
<div data-consent-hide="external">The map below needs your consent for external media. <button class="nino-consent-open" type="button">Cookie settings</button></div>
<div data-consent-show="external" hidden>Thanks - the map is loading.</div>
```

`consent.js` only ever *unhides* a `data-consent-show` element and *hides* a
`data-consent-hide` one for an allowed category - it never reverses either
in the other direction, so give the "not yet allowed" element its own
visible-by-default markup the way the example above does.

### Not for an iframe

**A hidden iframe is still fetched.** An iframe inside a container with the
`hidden` attribute, with `display:none` or with `visibility:hidden` loads exactly
like a visible one - measured in Chromium, all three reach the third-party
server. So `data-consent-show` around an iframe hides the map from the visitor
and sends their address to the provider anyway, which is the one thing this
feature is for.

The two patterns that do work: a `<script type="text/plain" data-consent="…">`
placeholder above, where nothing runs until the script is created, or the
[External Embeds](../Embed/README.md) feature, which carries the address in a
data attribute and builds the iframe when it is released - by a press, or by this
feature's own `external` category.

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

## CSP: the hosts a placeholder loads from

Nino's default `Content-Security-Policy` lets a script come from the site
itself (`default-src 'self'`, and `script-src 'self'` with the `[jstext]`
nonce). A released placeholder is a script from another host, so without more
the browser would block it - the visitor's consent would release nothing.

So this feature adds the host to the policy. On `/nino/http/output`, where the
finished page is in hand, it reads the page's
`<script type="text/plain" data-consent="…" data-src="https://host/…">`
placeholders and appends each host to the `script-src` of the response
(`script-src-elem` too, where the policy has one). `script-src` is extended in
place and never written twice; where the policy has none it is built from
`default-src`'s own list; a policy that says `'none'` is left as it is, and so
is one with no `default-src` to fall back to - that policy is unrestricted
already. A page with no placeholder keeps its policy byte for byte.

**Why this is allowed.** Nino's guide says not to add a remote source to the
policy merely to make one widget work. This is the exception, and it is
narrow: the visitor has to allow the category before anything is loaded, the
host is one the project named in its own template, and without it the feature
cannot do the one thing it is for. What it adds is the origin - not
`'unsafe-inline'`, not a wildcard, not a host nobody wrote.

**What the page's own markup decides.** The hosts come from the page, so
markup that reaches the page can name one. Three rules hold that down to what
a project wrote on purpose:

- only a placeholder of a category the site **offers** counts - `necessary`,
  and each optional category whose setting is on. Markup naming a category
  nobody offers opens nothing;
- only an `https` address counts, whose host is a plain ascii name: no
  credentials, no IP address, no wildcard, no `;` that would end the
  directive. A port is kept;
- an inline, relative, `http:` or protocol-relative placeholder adds nothing.

Texts and elements are escaped or stripped on their way into a page, so
today this is defence in depth and not a gap; but a template that prints
visitor-controlled markup unescaped can now widen the script policy for a
category the site offers, and that is the project's to keep out.

**What it does not cover:**

- an inline placeholder (see above);
- the hosts a released script loads further things from itself: a tag that
  pulls in a second script, a beacon (`connect-src`), a frame it opens. Each
  needs its own source, which a project adds to its policy itself;
- a script that redirects to another host - the policy is checked against the
  redirect target too;
- the maintenance page, which is answered before the output phase;
- a page the page cache answers (next section).

## Texts

Every word in the banner is a textfill the install unit writes into the
project's own `text/en_US.php`/`text/de_DE.php` at activation (add-only - a
key the project already has stays), so editors keep them current in the
Text panel from then on:

| Key | English default | German default |
| --- | --- | --- |
| `[[/feature/consent/banner/title]]` | We use cookies | Wir verwenden Cookies |
| `[[/feature/consent/banner/text]]` | We use cookies and similar technologies ... | Wir verwenden Cookies und ähnliche Technologien ... |
| `[[/feature/consent/banner/link]]` | Privacy policy | Datenschutzerklärung |
| `[[/feature/consent/action/accept-all]]` | Accept all | Alle akzeptieren |
| `[[/feature/consent/action/necessary-only]]` | Necessary only | Nur notwendige |
| `[[/feature/consent/action/save]]` | Save selection | Auswahl speichern |
| `[[/feature/consent/action/open]]` | Cookie settings | Cookie-Einstellungen |
| `[[/feature/consent/category-<name>/name]]` | the category's own label | ditto |
| `[[/feature/consent/category-<name>/hint]]` | one-line explanation | ditto |

`<name>` is `necessary`, `statistics`, `marketing` or `external`. A link to
the imprint, or any other markup, can be added straight into
`[[/feature/consent/banner/text]]` from the Text panel - fills are not escaped, so a
project is free to put a second `<a>` there; this feature only ever renders
the one privacy link it has a dedicated setting for (or, with that setting
empty, the Legal module's page).

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

The asset paths are `/features/Consent/assets/...`: `\Nino\Filesystem::path()`
resolves `/features/...` against the features directory, wherever
`NINO_FEATURES_DIR` put it. There is no older kernel to allow for - the
manifest names `^1.3`, and the kernels that resolved the path against the
project root cannot read this manifest at all.

The banner's markup is the same for every visitor - no per-visitor state is
rendered server-side, the choice itself is read and written by the browser
- so the kernel's full-page cache stays valid for the banner: if
`/nino/cache/status` is on, `[consent]` and `[consent-settings]` render into
the cached page exactly like any other shortcode.

The policy is the exception. The cache keeps the body and answers a hit with
the default policy, so a hit does not carry the hosts this feature added when
the page was rendered, and a placeholder on a cached page is blocked. Until
the kernel keeps the widened policy with the entry, list the pages that carry
placeholders under `/nino/cache/blacklist` (`/page` for one, `/section/*` for a
branch), or leave the cache off. A placeholder in the shared page frame is on
every page, so with the cache on that means the whole site.

## Relationship to the base install's own cookie banner

Nino up to 1.3.x shipped a plain accept/decline `.nino-cookie-banner` in the
base install's `templates/html-footer.tpl`, driven by `Nino.ui.cookieConsent`,
writing `'accepted'` or `'declined'` into a cookie named `nino_consent`. A
newer kernel ships none. A project set up with the older one still has the
block in its own `templates/html-footer.tpl` - the install is applied once and
never rewritten - and this feature deals with what is left:

- `consent.js` removes a `.nino-cookie-banner` it finds in the page, so a
  visitor never sees two banners, nor an unstyled leftover;
- it reads the old values - `'accepted'` counts as every category the site
  offers, `'declined'` as the necessary one alone - so a choice a visitor
  already made is kept until they change it.

Deleting the `<div class="nino-cookie-banner">` block from
`templates/html-footer.tpl` is the clean fix, and the only one for a page
without this feature. Scripts of the project that were gated on
`Nino.ui.cookieConsent` listen for the `nino:consent` event (or read
`<html data-consent>`) instead - see above.

## Privacy policy

`install/elements/privacy.php`, named under `elements` in
`install/manifest.php`, adds the section `consent` (position 420) to the type
`privacy` of the Legal module that comes with Nino 1.4 - add-only, as
everything an install unit does: a section an editor changed stays as it is,
one deleted for good does not come back, and a Nino without the module ignores
the file. The text says only what the code does, in German and English; a fact
of the website would be a placeholder of the module
(`#/project/company/contact/email#`), not written text.

- **What it says.** The choice is kept for 180 days in the cookie
  `nino_consent`, which holds nothing but the choice; the legal bases; and
  that the visitor can change or withdraw it at any time with the "Cookie
  settings" button at the end of the section.
- **The button.** `init()` registers a listener on `/nino/legal/section`, the
  callback the Legal module fires for every section it draws, and it appends
  the template of `[consent-settings]` to the section `consent` of the type
  `privacy` - to that one, and only while this feature is active. Where there
  is no Legal module nobody fires the callback, and nothing happens.
- **The link in the banner.** With the setting `policyUrl` empty, the banner
  links the privacy policy of the Legal module, `\Nino\Modules\Legal::url()`,
  in the visitor's language - when the module is there and its page has a
  route. An address in the setting wins. The call is guarded by
  `class_exists()` and not by `method_exists()`, which PHPStan reads as always
  false on a Nino without the class and as always true on one with it; on Nino
  1.3 there is no such class, and no link is shown unless the setting names
  one, as before.
- **The numbers.** The cookie name and the 180 days are the defaults of the
  settings `cookieName` and `days`. Whoever changes a setting changes the
  section in the Elements panel too.

The section is a starting point and no legal advice, like the texts of Nino's
own Legal module: it is not tailored to any particular website and has not
been legally reviewed, and the operator is responsible for having it checked
and adapted before the website goes live. The notice in full is in the
[Legal](https://github.com/dapeio/nino/blob/main/docs/development.md#legal)
chapter of Nino's `docs/development.md`.

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
`[consent]` rendering only the categories the settings enabled, as a dialog,
and the policy link only when `policyUrl` is set - or, with it empty, the
Legal module's privacy page where the checkout has the module - the button in
the privacy policy's own section, `[consent-settings]`,
`allowed()` reading the configured cookie name and refusing a disabled
category or an unknown one regardless of what an old cookie says, the
Content-Security-Policy gaining the host of an offered category's placeholder
and nothing else (every refusal of the CSP section above, and the merge into
a policy that has a `script-src`, has none, says `'none'` or has no
`default-src`), and deactivation leaving the settings and merged texts in
place. It loads Nino's `tests/harness.php` from the checkout three levels up -
where the feature sits in a project - or from the one `NINO_ROOT` names:

```bash
php features/Consent/tests/consent-smoke.php
NINO_ROOT=../nino php features/Consent/tests/consent-smoke.php
```

`tests/consent-js-smoke.js` is what `consent.js` does over a DOM stand-in, run
by the PHP test too where `node` is on the path: a stored choice shown by the
checkboxes and kept by "Save selection", a category the site does not offer
never allowed, the placeholders released for what is allowed and nothing else,
the older banner removed, the reopen button of a page without a banner hidden,
and where the focus goes.

`node --check features/Consent/assets/consent.js` and `eslint` (the
checkout's own `eslint.config.mjs`) check the script; PHPStan analyses the
class.

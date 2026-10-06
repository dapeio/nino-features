# Changelog

All notable changes to the Consent feature are documented in this file.
A release is the tag `consent-<version>` of dapeio/nino-features.

## Unreleased

- Needs Nino `^1.3`, where the constraint said `^1.1`. The sectioned `manual`
  map this manifest carries is only read by a kernel newer than the
  `v1.2.0-beta` tag - `Features::manifest()` refuses it on the tagged one -
  and `^1.1` is satisfied by that kernel, so the catalogue offered the
  feature to an installation that could not then install it. `^1.3` names
  only a kernel that can read the manifest.

### Added

- **It brings its section of the privacy policy, links the policy by itself and
  puts its button there.** The install unit adds the section `consent` (cookie
  `nino_consent`, 180 days, the legal bases, withdrawal) to the type `privacy`
  of Nino 1.4's Legal module - add-only, so a section an editor changed or
  deleted is not touched again - and a Nino without the module ignores the file.
  With no `policyUrl` the banner links the module's privacy policy in the
  visitor's language; an address in the settings still wins, and without the
  module no link is shown, as before. A listener on `/nino/legal/section`
  appends the "Cookie settings" button to that section, so the withdrawal the
  text speaks of can be done where it is described. The text names the cookie
  and the 180 days as the settings' defaults: whoever changes `cookieName` or
  `days` changes the section too. It is a starting point, not legal advice - see
  "Legal" in Nino's `docs/development.md`. `nino` stays `^1.3`.

### Changed

- **The text keys follow Nino's grammar.** `/consent/title`, `text` and
  `policy-label` are `/feature/consent/banner/title`, `text` and `link`;
  `/consent/accept-all`, `necessary-only`, `save` and `open` are
  `/feature/consent/action/accept-all`, `necessary-only`, `save` and `open`;
  `/consent/category/<name>` and `…/hint` are
  `/feature/consent/category-<name>/name` and `…/hint`, for `necessary`,
  `statistics`, `marketing` and `external`. The values are the same. Nothing has
  been published under the old keys, so there is no migration; a project that
  already has texts under them copies the values to the new keys. `nino` stays
  `^1.3`: the keys are the feature's own, and a Nino before 1.4 takes them as
  they come.

- **The banner is a dialog, and its three actions look alike.** `[consent]`
  carries `role="dialog"`, `aria-labelledby` and `aria-describedby` (ids on the
  title and the text) and `tabindex="-1"`. Reopened from a `[consent-settings]`
  button it takes the focus and gives it back to that button once a choice is
  made; nothing traps the focus meanwhile - it is a notice, not a modal.
  "Accept all" lost `nino-consent-btn--primary`, and the rule went with it:
  accepting everything is no easier to press than refusing it. The banner is
  this feature's own template, so it reaches every project that updates; a
  project stylesheet that styled `.nino-consent-btn--primary` has nothing left to
  style.

- **The German texts of the install unit address the reader as "Du".**
  `[[/consent/text]]` and `[[/consent/category/external/hint]]` said "Ihrer
  Einwilligung", "Sie" and "Ihre IP-Adresse"; they say "Deiner Einwilligung", "Du"
  and "Deine IP-Adresse" now. The unit is applied add-only: a project that
  already activated the feature keeps its wording, activating again does not
  replace it, and the two keys can be edited in the Text panel.

- **The README no longer names a kernel version for the older banner.** Nino
  up to 1.3.x shipped a plain `.nino-cookie-banner` in the base install's
  `templates/html-footer.tpl`; a newer kernel ships none. "Relationship to the
  base install's own cookie banner" says what this feature does with a leftover
  block (`consent.js` still removes it and still reads its `'accepted'` and
  `'declined'`) and that deleting the block is the clean fix. Scripts that were
  gated on `Nino.ui.cookieConsent` listen for `nino:consent` instead. "Put it on
  the site" says plainly that activating the feature shows nothing until
  `[consent]` is in the page frame, and so does the manual entry in the Features
  panel. The comments in `consent.js` and `Consent.php` name the banner by what
  it was, not by `Nino.ui.cookieConsent`. No code changed for this.

- **The words described a feature that had moved on.** The README listed the
  directory's contents without the `templates/` the banner's markup lives in
  now, and said the manifest carries no `data` entry where it carries an empty
  one; it had `consent.js` unhiding the banner on the page's `load`, where the
  script runs on `DOMContentLoaded`; and it still explained what an older
  kernel does with a `/features/...` path, which cannot happen since the
  manifest names `^1.3`. In the class, the comment on the category list had
  ended up above `TEMPLATES` when that constant was added, and `init()` still
  described the asset source resolving against the project root instead of
  against `\Nino\Features::dir()`. Class and script both named a README
  section that does not exist under that name. Words only - the code is
  unchanged.

- **The banner's markup is a template now.** The banner, a category row, the
  open button and the policy link were strings `Consent.php` assembled; they
  are `templates/consent-banner.tpl`, `consent-category.tpl`,
  `consent-open.tpl` and `consent-policy-link.tpl`, filled by token with every
  value escaped before it goes in - see AGENTS.md, "Markup belongs in a
  template". The output is the same.

### Fixed

- **Hex numbers and a trailing newline in what the policy is built from.** A
  host whose last label is a hex number (`0x7f000001`) is an IP address in a
  form PHP does not call one and is refused like `2130706433` was; the host
  patterns carry `D`, so `$` does not also match before a newline at the end.
  The non-ASCII host test was written in single quotes, where `\u{e4}` is not an
  escape, and tested nothing.

- **A `script-src` with no source was widened.** It blocks everything, like
  `'none'`, and is the project's decision just the same - it is left alone now,
  and so is a `default-src` of that kind it would be built from.

- **The workbench's own policy was extended.** A response of `/_admin` or below
  is skipped.

- **A hidden `[consent-settings]` button could show anyway.** A rule that gives it
  a `display` beats the browser's `[hidden]`; `.nino-consent-open[hidden]` is
  `display: none` now.

- **A consent-gated script from another host was blocked by the browser.**
  `consent.js` releases a `<script type="text/plain" data-consent="…"
  data-src="https://…">` by cloning it into a real script, and the policy Nino
  ships lets a script come from the site alone - so a visitor's consent released
  a script the browser then refused. The feature now adds the host to the
  response's `script-src` (and `script-src-elem` where the policy has one). The
  manual says which callback it registers: `/nino/http/output`.

  It stands on the output phase and not on the response phase, because the
  hosts are known only once the body is rendered, and every response hook runs
  before that. `script-src` is extended in place and never written twice, built
  from `default-src`'s list where the policy has none, and left alone where it
  says `'none'` or the policy has no `default-src` to fall back to. A page with no
  placeholder keeps its policy byte for byte. Only a placeholder of a category the
  site offers counts (`necessary` and the optional ones whose setting is on), and
  only an `https` host that is a plain ascii name - no credentials, no IP address,
  no wildcard - so markup that reaches the page cannot open the policy for a host
  of its own. The README's "CSP" section gives the reasoning, and the limits: an
  inline placeholder does not run under this policy, the hosts a released script
  loads from itself are not covered, and neither is the maintenance page.

  **A page the page cache answers does not carry the added host.** The cache
  keeps the body and answers a hit with the default policy. Until the kernel
  keeps the widened policy with the entry, list the pages that carry placeholders
  under `/nino/cache/blacklist`, or leave the cache off.

- **A category the site had switched off was allowed in the browser.**
  `parseAllowed()` kept every category in the cookie and read the older banner's
  `'accepted'` as all four, so the placeholders of a category the settings turned
  off were released although `allowed()` refuses it. It is cut to what the banner
  offers now. A page that renders no `[consent]` has no offer to read, and keeps
  the stored list.

- **Reopening the banner dropped the stored choice.** The checkboxes were never
  set from the cookie, so "Save selection" after a reopen wrote `necessary` alone
  - a quiet revocation. They show what is stored now: `accepted` checks every
  offered box, a first visit and "Necessary only" leave them unchecked.

- **The reopen button of a page with no banner did nothing.** It is hidden now.

- **An invalid byte in a value rendered as nothing.** `htmlspecialchars()`
  answers input that is not valid UTF-8 with `''` unless `ENT_SUBSTITUTE` is
  among its flags, and every call here spelled the flags out without it.
  Every call carries it now, as the kernel's do, and
  `tests/escaping-smoke.php` reads every feature for the next one.

- **A cookie anybody can send was a 500 on every page.** `allowed()` cast the
  consent cookie to a string, and a request sending `nino_consent[]=x` makes
  that cast an "Array to string conversion" - a level the kernel treats as
  fatal. Every page that asks whether something is allowed answered 500 for
  as long as the cookie was sent, which is as long as the sender likes. The
  cookie is read as a string or not at all now, and so is the cookie name
  from the settings: a hand-edited `config.php` is no more typed than a
  request is.

- **README.md advised hiding an iframe, which does not stop it.** The example
  for `data-consent-show` put a map iframe inside a `hidden` container, and a
  hidden iframe is fetched exactly like a visible one - measured in Chromium for
  the `hidden` attribute, `display:none` and `visibility:hidden` alike. The
  visitor's address reached the provider before the banner was answered, which
  is the one thing this feature is for. The example now toggles a notice rather
  than a frame, and a new section says plainly that the pattern is not for an
  iframe and names the two that work: the `<script type="text/plain">`
  placeholder this feature already has, and the External Embeds feature.

## 1.0.0 — 2026-09-08

- First release: a cookie/consent banner without a third party -
  `necessary`/`statistics`/`marketing`/`external` categories, the settings
  saying which of the optional three a site uses, `[consent]` and
  `[consent-settings]`.
- `consent.js` reads and writes the one consent cookie, activates
  `<script type="text/plain" data-consent="...">` placeholders once a
  category is allowed, toggles `data-consent-show`/`data-consent-hide`, and
  dispatches `nino:consent` - no dependencies, no build step.
- `\Nino\Modules\Consent::allowed()` for a server-side, read-only check of
  the same cookie.
- `consent.css`/`consent.js` ship through the project's own `/.cache/style.css`/
  `/.cache/script.js` bundles (`\Nino\Html::addAsset()`), the same mechanism
  the kernel uses for `Nino.css`/`Nino.js`.
- English and German banner texts via the install unit, add-only into the
  project's `text/<locale>.php`.

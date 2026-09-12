# Light/Dark Switch

Three states, one control: read the site light, read it dark, or read it the way
the system asks — which is where it starts, and the only one of the three that is
not a decision.

```
[mode-switch]
```

That is the whole interface. Put it in the header template, the footer, a
settings page, or all three; a site with several switches has one switch, shown
in several places.

```
[mode-switch icons]
```

leaves out the words and keeps the icons, which is what a crowded header bar
usually wants. The words stay in the markup either way — a screen reader still
reads three named buttons, because three unlabelled icons are not a control
anybody can use.

## Dark is not something this invents

Every Nino project's `assets/theme.css` already publishes a dark palette beside
its light one, under two selectors:

```css
@media (prefers-color-scheme: dark) { :root:not([data-nino-mode="light"]) { … } }
:root[data-nino-mode="dark"] { … }
```

The first is for a reader who has said nothing, the second for one who has. This
feature is the control that writes that attribute, and that is the whole of it —
which is why it works on a project that never installed the Design feature, and
why a project that did gets its own compiled colours rather than something this
feature chose.

The three states are therefore **two values and an absence**:

| On screen | `data-nino-mode` |
| --- | --- |
| Light | `light` |
| System | *(no attribute)* |
| Dark | `dark` |

The middle position removes the attribute rather than writing a third value. That
is not a shortcut — it is what makes *System* keep working when the reader changes
their system setting with the page open: there is no stored state to go stale,
only an absence the browser re-answers on its own.

A forced mode also sets `color-scheme`, so the browser's own furniture —
scrollbars, form controls, the canvas behind the page — comes from the mode the
reader chose rather than from the system setting they just overrode.

## Nothing reaches the server

The choice lives in the reader's own browser, under `localStorage['nino-mode']`.

- No cookie, so nothing to declare and nothing for a consent banner to gate.
- Nothing sent anywhere, so no route, no request, no log line.
- A cached page is as switchable as a fresh one: the attribute is written onto
  the page after it arrives, never into it.

A browser that refuses storage — a private window, storage switched off — throws
on both read and write. Every access is wrapped, so the switch then works for the
length of the visit instead of not at all.

## What a reader sees first

`modeswitch.js` applies the stored choice as its first statement, at parse time,
rather than waiting for the DOM. It is bundled into the project's own
`/.cache/script.js`, which the base install loads at the end of `<body>` — so a
reader whose stored choice differs from their system setting may see the first
paint in the other mode on a long page.

A project that wants none of that at all moves

```
[assets /.cache/script.js]
```

from `templates/html-footer.tpl` into `templates/html-header.tpl`. The bundle
then blocks rendering, which is the trade: no flash, one round trip before the
first paint.

The switch itself renders `hidden` and is unhidden by the script. Without
JavaScript there is no control at all rather than three buttons that do nothing,
and the reader keeps the mode their system asks for — which is what they had
before this feature existed.

## The words

Four text fills, merged into the project's own `text/<locale>.php` at
activation, add-only:

| Key | English | Deutsch |
| --- | --- | --- |
| `/modeswitch/label` | Appearance | Darstellung |
| `/modeswitch/light` | Light | Hell |
| `/modeswitch/system` | System | System |
| `/modeswitch/dark` | Dark | Dunkel |

From then on they are the project's: an editor changes them in the Text panel and
never opens a feature directory. A key the project already had is left alone.

## Asset bundling

`modeswitch.css` and `modeswitch.js` are added to the **site's own** bundles —
`/.cache/style.css` and `/.cache/script.js` — the same two the base install's
`html-header.tpl`/`html-footer.tpl` already load on every page, and the same way
the kernel bundles its own `Nino.css`/`Nino.js`. A switch is written into one
template and read on all of them, so a bundle of its own would be a second
request for two small files every page needs anyway.

The sources are addressed as `/features/Modeswitch/assets/…`, which
`\Nino\Filesystem::path()` resolves against the project root. A project that
moved its features elsewhere with `NINO_FEATURES_DIR` has to say so in
`/nino/html/assets` itself, the same as for every other feature that ships a
static asset.

## Settings

None, and that is the design. Which of the three a reader is on is not the site's
decision — it is *whatever this reader chose last, and their system until they
choose*. The only thing a project varies is whether the buttons carry their
words, and that belongs to the one place the switch is written, not to a setting
that would have to mean the same thing in the header and in the footer.

## Data

None. The choice belongs to the reader's browser and is kept there; a site that
stored it would be storing a preference about a person, which is a consent
question this feature deliberately does not raise.

## Tests

`tests/modeswitch-smoke.php` — the manifest, the activation and the four words it
merges, the shortcode and the markup it renders, the two files it puts into the
site's bundles, and deactivation. It runs `tests/modeswitch-js-smoke.js` too
where `node` is on the path.

`tests/modeswitch-js-smoke.js` — what `modeswitch.js` does over a DOM stand-in:
which of the three it starts on, what each of them writes on the root element,
that the middle one writes nothing, that the choice survives a reload, and that a
browser refusing storage gets a switch that works for the visit instead of one
that throws.

```bash
php features/Modeswitch/tests/modeswitch-smoke.php
node features/Modeswitch/tests/modeswitch-js-smoke.js
```

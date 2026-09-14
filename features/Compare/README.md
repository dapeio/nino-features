# Before/After

Two pictures of the same thing under one divider the visitor moves.

```
[compare before="haus/roh.jpg" after="haus/fertig.jpg" alt="Die Fassade vor und nach der Sanierung"]
[compare before="a.jpg" after="b.jpg" before-label="Rohbau" after-label="Fertig" ratio="16-9" start="20"]
```

## The divider is a range control

Not a `<div>` with pointer handlers on it. An `<input type="range">` is dragged
with a mouse, with a finger *and* with the arrow keys; it announces itself and
its value to a screen reader; and it is one line of markup instead of a gesture
library that would have to be told about all three, and told again for the next
input device.

What `compare.js` does is read that control's value into
`--nino-compare-position`. The clipping is `compare.css`'s, so what a browser
does on every frame of a drag is a paint — not a call into JavaScript.

## Without JavaScript

The same markup is **two captioned pictures under one another**. That is not a
fallback bolted on: it is what the markup reads like, and `compare.js` adds
`.nino-is-ready`, which is what turns the two into a stack under a divider. A
visitor whose browser never runs it sees both pictures and both captions — which
is what the pair was there to show.

The control is written `hidden` for the same reason `[mode-switch]` writes its
own that way: a slider that slides nothing is worse than no slider.

## Attributes

| Attribute | What it does |
| --- | --- |
| `before=` / `after=` | the two filenames, below the project's own images. Both are needed — half a pair renders nothing at all |
| `alt=` | what the pair shows, for a visitor who cannot see it. It becomes the first picture's `alt` *and* the caption under the pair. **Say it**: two pictures with no description are two decorations |
| `before-label=` / `after-label=` | what the two sides are called. Without them the Text panel's own words are used |
| `start=` | where the divider stands when the page opens, `0`–`100`. Default `50` |
| `ratio=` | `16-9`, `4-3` (default), `1-1` or `3-2` |

The second picture is `aria-hidden` and its caption sits inside the clipped box:
to a screen reader the pair is **one** picture with a description, which is what
it is, plus a named control that changes how much of it is showing.

## The words

`install/text/<locale>.php` carries three fills, merged into the project's own
`text/<locale>.php` at activation, for every locale it has:

| Fill | English |
| --- | --- |
| `[[/compare/before]]` | Before |
| `[[/compare/after]]` | After |
| `[[/compare/handle]]` | Move the divider between the two pictures |

From then on they are the project's: an editor changes them in the Text panel and
never opens a feature directory. A key the project already had is left alone.

## Asset bundling

`compare.css` and `compare.js` are added to the **site's own** bundles —
`/.cache/style.css` and `/.cache/script.js` — the same two the base install's
`html-header.tpl`/`html-footer.tpl` already load on every page, and the same way
the kernel bundles its own `Nino.css`/`Nino.js`.

The sources are addressed as `/features/Compare/assets/…`, which
`\Nino\Filesystem::path()` resolves against the project root. A project that
moved its features elsewhere with `NINO_FEATURES_DIR` has to say so in
`/nino/html/assets` itself.

## Settings

None. Everything a comparison varies — which two pictures, what the sides are
called, where the divider starts, the shape of the box — belongs to the one place
it is written. A before/after of a facade and one of a photo retouch are not the
same decision, and a setting would have to mean both.

## Data

None. The two pictures are the project's own images, and where the divider stands
is the visitor's, for as long as they look.

## Tests

`tests/compare-smoke.php` — the manifest, the activation and the three words it
merges, the shortcode over both pictures and every way of getting it wrong, the
two files it puts into the site's bundles, and deactivation. It runs
`tests/compare-js-smoke.js` too where `node` is on the path.

`tests/compare-js-smoke.js` — what `compare.js` does over a DOM stand-in: that it
takes the control out of hiding, writes its value into the one custom property
the clipping reads, brings a value outside the control's range back into it, and
leaves a pair with no control exactly as it was.

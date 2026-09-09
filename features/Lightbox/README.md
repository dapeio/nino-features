# Lightbox

**Key:** `lightbox` · **Class:** `\Nino\Modules\Lightbox` · **Version:** 1.0.0 · **Nino:** `^1.1`

A link that points at an image opens it full screen instead of navigating
away, with every other link of its group as the rest of the set: arrows and
swipe between them, the caption underneath, Escape and the backdrop to close,
and the focus put back on the link that opened it.

No library, no build step, and no dependency on `Nino.js` either - the two
files below are bundled into the project's own `/.cache/style.css` and
`/.cache/script.js`, the same way the kernel bundles its own.

What it opens is markup the page already carries. It knows nothing about
where the images came from: a gallery's thumbnails, a figure in an article, a
link an editor wrote by hand. The [Gallery](../Gallery/README.md) feature
requires this one and renders links it understands, but it is not the only
thing that can.

## How a link joins in

```html
<a href="/images/big.jpg" data-lightbox="trip" data-caption="Above the pass">
	<img src="/images/thumb.jpg" alt="Above the pass">
</a>
```

| Attribute | What it does |
| --- | --- |
| `data-lightbox` | opts the link in. Its **value is the group**: two galleries on one page stay two sets. An empty value is a set of one |
| `data-caption` | the caption under the picture. Without one, the `<img>`'s `alt`, then the link's `title` - never the filename |
| `data-label-close`, `data-label-prev`, `data-label-next` | the labels the controls carry for a screen reader, English without them. A static asset cannot read a textfill, so a multilingual site writes them in the template - `[[/lightbox/label/close]]` and friends, in your own text keys |

The `href` has to be an image (`jpg`, `jpeg`, `png`, `gif`, `webp`, `avif`,
`svg`). A link that opted in but points at a PDF is left to the browser: the
overlay would show a broken picture with no way back to the page.

## What it does

- **Opens on click**, unless the click was modified - a ctrl/cmd/shift/alt or
  middle click is the visitor asking for a tab of their own, which is what the
  `href` already gives them.
- **Moves** with the left and right arrow keys, the two buttons, or a
  horizontal swipe of more than 48px. It wraps at both ends. The two
  neighbours are preloaded, so an arrow press has nothing to wait for - never
  more than one in each direction, so a set of forty images is not forty
  downloads.
- **Closes** with Escape, the close button, or a click on the room around the
  picture. A click on the picture itself is somebody looking, not somebody
  leaving.
- **Keeps the keyboard inside** while it is open: Tab cycles the controls, and
  the focus goes back to the link that opened it on close.
- **Locks the page behind it** - the class goes on `<html>`, so it holds
  whether or not the body is the scroller.

## Without JavaScript

Every rule in the stylesheet hangs off `.nino-lightbox`, which only exists
once the script has opened one. A visitor without JavaScript gets a thumbnail
that is a link to a bigger picture, which works. A visitor who asked for
reduced motion gets the lightbox with the animation off, not a page without a
lightbox.

## Styling

Two custom properties, both on `:root`:

| Property | Default |
| --- | --- |
| `--nino-lightbox-backdrop` | `rgba(12, 14, 18, .94)` |
| `--nino-lightbox-ink` | `rgba(255, 255, 255, .92)` |

That is deliberately all of it. A lightbox is a dark room with a picture in
it; there is no site design to match, because the site is behind the backdrop.

## Asset bundling

`init()` adds the two files to `/.cache/style.css` and `/.cache/script.js`
under `/features/Lightbox/assets/...`, which `\Nino\Filesystem::path()`
resolves against the project root. A project that moved its features
directory with `NINO_FEATURES_DIR` has to add them under the path they
actually live at instead.

## Tests

```bash
NINO_ROOT=../nino php features/Lightbox/tests/lightbox-smoke.php
```

The manifest, the activation, the two files reaching the bundles, and what
the stylesheet and the script promise. `lightbox-js-smoke.js` beside it
measures the behaviour over a dom stand-in - which links it takes and which
it leaves alone, the set, the captions, moving with keys and swipes, the
focus trap, and the page lock - and the PHP test runs it where node is on the
path.

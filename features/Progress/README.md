# Reading Progress

A thin bar that says how far through a long text the reader is.

```html
<div class="nino-progress" data-progress-of="#article" data-progress-label="Lesefortschritt"></div>
```

This feature renders nothing. The bar is one empty element a project's own
template carries, exactly like [Typewriter](../Typewriter/README.md) — the class
is the two lines that put `progress.css` and `progress.js` into the site's
bundles.

## What is measured

A choice worth making. **The whole page** counts the footer as part of the
article, so "finished" arrives after the last paragraph rather than at it.
`data-progress-of="#article"` names the one element that *is* the text, and then
the bar is full when the text is.

What is travelled is the element's height less one screenful: a text is read when
its last line has been, not when its bottom edge reaches the top of the window.
An element shorter than the screen is finished the moment it is on it, rather
than never.

A bar pointed at an element that is not there falls back to the whole page.

## Attributes

| Attribute | What it does |
| --- | --- |
| `data-progress-of=` | a CSS selector: measure that element rather than the whole page |
| `data-progress-label=` | what the bar is, for a reader who cannot see it |

## The label decides what a screen reader gets

With one, the bar is a named `progressbar` with a value that follows the reading.
**Without** one it is `aria-hidden` — because a progressbar with no name is
announced as a number nobody asked for, which is worse than a decoration a screen
reader never mentions.

## Before the script, and with less motion

The bar is `display:none` until `progress.js` has measured something: a bar that
cannot move is a stripe across somebody's page, and an empty one is worse than an
honest absence.

The filled part eases towards its new width by 80ms, which reads as smooth while
scrolling — and not at all under `prefers-reduced-motion`, where the value itself
is what is shown with nothing in between.

Scrolling costs one animation frame at most, not one call per event, and the
width is a custom property the stylesheet uses, so what happens per frame is a
paint.

## Asset bundling

`progress.css` and `progress.js` are added to the **site's own** bundles —
`/.cache/style.css` and `/.cache/script.js` — the same two the base install's
`html-header.tpl`/`html-footer.tpl` already load on every page.

The sources are addressed as `/features/Progress/assets/…`, which
`\Nino\Filesystem::path()` resolves against the project root. A project that moved
its features elsewhere with `NINO_FEATURES_DIR` has to say so in
`/nino/html/assets` itself.

## Settings

None. Where the bar sits and what it measures belong to the one template it is
written into, and a static asset could not read a site-wide setting anyway.

## Data

None.

## Tests

`tests/progress-smoke.php` — the manifest, the activation, the two files it puts
into the site's bundles, what those two files promise, and deactivation. It runs
`tests/progress-js-smoke.js` too where `node` is on the path.

`tests/progress-js-smoke.js` — what `progress.js` does over a DOM stand-in: how
far through it thinks the reader is over the whole page and over one named
element, what it does with an element shorter than the screen, and what a screen
reader gets with and without a label.

# Ticker

A row that runs and starts again without a seam — a bar of logos, a line of
references, a strip of announcements.

```html
<div class="nino-ticker" data-ticker-speed="40">
	<div class="nino-ticker-track">
		<img src="…" alt="Kunde A">
		<img src="…" alt="Kunde B">
		<img src="…" alt="Kunde C">
	</div>
</div>
```

This feature renders nothing. What runs past is markup a project's own template
already carries, exactly like [Typewriter](../Typewriter/README.md) — the class
is the two lines that put `ticker.css` and `ticker.js` into the site's bundles.

## The seam is the whole problem

A row that simply scrolls runs out and jumps back, and the jump is what everybody
sees. So `ticker.js` copies the row's own children until they are wider than the
box plus one length of the row, and moves the whole of it by **exactly one
original width** — at which point the copy is standing where the original stood,
and starting again from zero moves nothing.

The copies are `aria-hidden`: to a screen reader the row is read **once**, which
is how many times it is there.

How many of them are needed follows from the box the row stands in, so the row
is measured and copied again when that box changes width — a window resized, a
phone turned — once the resizing has come to rest. Copies made for a narrower
box cover a wider one only in part, and what runs past for the rest of every
cycle is nothing at all.

The movement is a CSS animation, not a script moving something every frame. A
browser runs an animation off the main thread and stops paying for it in a
background tab; neither is true of a timer.

## Attributes

| Attribute | What it does |
| --- | --- |
| `data-ticker-speed="40"` | pixels per second. Default 40 — slow enough to read a word on the way past |
| `data-ticker-direction="right"` | runs the other way. Default is to the left |
| `data-ticker-pause="off"` | keeps running under the pointer. Default is to stop, so a logo can be looked at |

A row also stops while something inside it has the keyboard focus: a row that
runs away under a tab stop is a row nobody can use.

## Without JavaScript, and with less motion

Before the script has measured anything, the track is a plain flex row — the
logos side by side, which is what the markup reads like. That is also what a
visitor who asked for less motion keeps: under `prefers-reduced-motion` the row
stands still and scrolls by hand. A row running across the screen is the most
literal reading of "less movement" there is.

## Asset bundling

`ticker.css` and `ticker.js` are added to the **site's own** bundles —
`/.cache/style.css` and `/.cache/script.js` — the same two the base install's
`html-header.tpl`/`html-footer.tpl` already load on every page.

The sources are addressed as `/features/Ticker/assets/…`, which
`\Nino\Filesystem::path()` resolves against the project root. A project that moved
its features elsewhere with `NINO_FEATURES_DIR` has to say so in
`/nino/html/assets` itself.

## Settings

None, for the reason Typewriter has none: every timing belongs to the row being
run, not to the site — a logo bar in a footer and a line of announcements in a
header are not one speed — and a static asset could not read a site-wide setting
anyway.

## Data

None.

## Tests

`tests/ticker-smoke.php` — the manifest, the activation, the two files it puts
into the site's bundles, what those two files promise, and deactivation. It runs
`tests/ticker-js-smoke.js` too where `node` is on the path.

`tests/ticker-js-smoke.js` — what `ticker.js` does over a DOM stand-in: how many
copies it makes for a given row and box, that it stops making them, that the
copies are hidden from a screen reader, and what it sets the animation's distance
and duration to.

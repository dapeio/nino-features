# Typewriter

**Key:** `typewriter` · **Class:** `\Nino\Modules\Typewriter` · **Version:** 1.0.0 · **Nino:** `^1.1`

A container types its lines one after the other: fade one in, write it out
character by character with the cursor riding at the writing head, hold it,
take it away again, then the next one - looping, or stopping on the last
line. The lines are the container's own `<p>`s, and every timing is a data
attribute on that same container.

Four files, no panel, no settings, no state: `feature.php`, `Typewriter.php`
- two lines that put the stylesheet and the script into the site's own
bundles - and `assets/typewriter.css`/`assets/typewriter.js`. The changes
per version are in [CHANGELOG.md](CHANGELOG.md).

## Put it on the site

Write the container into a template, a section preset or an HTML+ block,
with one `<p>` per line:

```html
<div class="nino-typewriter nino-section-title">
	<p>We build websites.</p>
	<p>We build them to last.</p>
	<p>We build them for you.</p>
</div>
```

That is the whole markup contract. The container is styled by whatever
classes it already carries - `nino-section-title` here - and the typewriter
adds nothing to its look: it is the same text in the same type, written out
instead of simply standing there.

It starts when it has been scrolled into view, types at 45 ms per character,
holds a finished line for 1.6 seconds, fades it out over 400 ms, waits
another 300 ms, and starts over after the last line. Every one of those
numbers is an attribute away.

## The data attributes

All of them are optional, all of them are read off the container itself, and
an unreadable or negative value keeps the default rather than timing the
animation with a `NaN`.

| Attribute | Default | What it does |
| --- | --- | --- |
| `data-typewriter-lines` | `p` | CSS selector of the lines inside the container |
| `data-typewriter-start` | `view` | `view` starts when the container is scrolled into view, `load` right away |
| `data-typewriter-start-delay` | `0` | Milliseconds before the first line - once, not per pass |
| `data-typewriter-speed` | `45` | Milliseconds per typed character |
| `data-typewriter-hold` | `1600` | Milliseconds a finished line stands |
| `data-typewriter-exit` | `fade` | `fade` fades the line out, `backspace` erases it character by character |
| `data-typewriter-fade` | `400` | Milliseconds of the fade in and out; `0` switches it off. Not used by `backspace` |
| `data-typewriter-backspace-speed` | `25` | Milliseconds per erased character, with `exit=backspace` |
| `data-typewriter-pause` | `300` | Milliseconds between one line leaving and the next arriving |
| `data-typewriter-loop` | on | `0`, `false`, `off` or `no` stops on the last line instead of starting over |
| `data-typewriter-cursor` | `\|` | The cursor character; empty (`data-typewriter-cursor=""`) leaves it out |

A typewriter that writes one claim slowly and then stops, erasing as it goes:

```html
<div class="nino-typewriter" data-typewriter-exit="backspace" data-typewriter-speed="70"
	data-typewriter-hold="900" data-typewriter-cursor="_" data-typewriter-loop="0">
	<p>Handmade in Munich.</p>
	<p>Since 1998.</p>
</div>
```

There are no site-wide settings, and that is deliberate: what a typewriter is
timed with belongs to the element being typed - two of them on one page rarely
want the same rhythm. `typewriter.js` is a static asset, never rendered
through the fill engine ([Assets Are Not Templates](https://github.com/dapeio/nino/blob/main/docs/development.md)),
so a setting in `config.php` could not reach it in the first place.

## Nothing moves while it writes

Two things a typewriter usually gets wrong, and what this one does instead:

- **The line grows.** `typewriter.js` splits every line in two - what is
  typed, and what is not typed yet in a `.nino-typewriter-rest` span that
  `visibility: hidden` keeps laid out. The line is always the size of its
  finished self, breaks where it will break, and the text below never gets
  pushed down a row as a line wraps.
- **The lines are different lengths.** They share one CSS grid cell, so the
  container is as tall as its longest line from the start, not as tall as
  whichever line is currently showing.

The cursor is `width: 0` and drawn over the character it stands in front of,
so that it too moves nothing: a cursor with a width of its own would shift the
rest of the line by that width with every character, and rewrap it as it goes.

## Screen readers, no JavaScript, reduced motion

The typed text and the cursor are `aria-hidden`; beside them, each line keeps
its own complete text once in a clipped `.nino-typewriter-reader` span. A
screen reader therefore reads the lines the way the markup has them - all of
them, in order, once - instead of one character at a time.

Every rule in `typewriter.css` hangs off `.nino-typewriter-line`, the class
the script writes when it takes over. So wherever the script does not run,
not one of them matches and the container is exactly what it reads like in
the markup - paragraphs below one another:

- **without JavaScript**, and
- **for a visitor who asked for reduced motion**: `typewriter.js` checks
  `prefers-reduced-motion: reduce` before it changes anything and, when it
  is set, leaves the markup alone entirely. There is no attribute to
  override that.

## Asset bundling

`typewriter.css`/`typewriter.js` reach the browser the same way the kernel
ships its own `Nino.css`/`Nino.js`/`Nino.ui.js`: `init()` calls
`\Nino\Html::addAsset()` to add them to the project's **own**
`/.cache/style.css` and `/.cache/script.js` bundles - the exact targets the
base install's `html-header.tpl`/`html-footer.tpl` already load on every page
via `[assets /.cache/style.css]`/`[assets /.cache/script.js]` - rather than a
bundle of this feature's own. A typewriter is written into whatever page
wants one, and every one of them already loads those two.

The asset paths are `/features/Typewriter/assets/...`: a kernel from the
catalogue release on resolves `/features/...` through
`\Nino\Filesystem::path()` against the features directory, wherever
`NINO_FEATURES_DIR` put it. An older kernel resolves it against the project
root, which is the same place as long as `features/` is not relocated.

Nothing is rendered server-side, so the kernel's full-page cache stays valid
with this feature active: the markup in the cached page is the project's own,
and the browser does the rest.

## What it does not do

- No shortcode and no route: the container is markup a template writes, the
  way `nino-vpa`, `nino-slider` and every other frontend effect Nino ships
  is markup a template writes.
- It does not type HTML. A line's text is read with `textContent` and
  written back the same way, so a `<strong>` inside a `<p>` is typed as the
  words it contains, not as markup - and nothing a project's text can carry
  ever becomes markup on the way through.
- No per-word or per-line reveal, no sound, no shuffle or scramble effect:
  one character at a time, forwards, and backwards where `exit=backspace`
  says so.
- It does not fix the container's height for you: a container whose lines
  differ in height is as tall as its tallest line, which is the point - but
  a container with one very long line is that tall from the start.

## Tests

`tests/typewriter-smoke.php` is the feature's own test: the manifest,
activation recording the class and the version without writing a single file
into the project, the real `\Nino\Html::addAsset()`/`[assets ...]` bundling
end to end (the generated `/.cache/style.css`/`script.js` genuinely carry
this feature's files), the two promises the files themselves make - every
rule bound to the class the script writes, the rest span and the zero-width
cursor - and deactivation. It loads Nino's `tests/harness.php` from the
checkout three levels up, where the feature sits in a project, or from the
one `NINO_ROOT` names:

```bash
php features/Typewriter/tests/typewriter-smoke.php
NINO_ROOT=../nino php features/Typewriter/tests/typewriter-smoke.php
```

`tests/typewriter-js-smoke.js` is the behaviour half, and the bigger of the
two: `typewriter.js` evaluated against DOM stand-ins and a clock the test
moves itself, measuring the whole sequence - the viewport start, every data
attribute, the values it refuses, the emoji it does not cut in half, the
markup it leaves for a screen reader and the markup it leaves alone under
reduced motion. `typewriter-smoke.php` runs it through `node` where node is
on the path, and says so when it is not; it also runs on its own:

```bash
node features/Typewriter/tests/typewriter-js-smoke.js
```

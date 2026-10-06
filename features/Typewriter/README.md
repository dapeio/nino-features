# Typewriter

**Key:** `typewriter` · **Class:** `\Nino\Modules\Typewriter` · **Version:** 1.0.0 · **Nino:** `^1.3`

A container types its lines one after the other: fade one in, write it out
character by character with the cursor riding at the writing head, hold it,
take it away again, then the next one - stopping on the last line, or looping
where the container asks for it. The lines are the container's own `<p>`s, and
every timing is a data attribute on that same container.

No panel, no settings, no state: `feature.php`, `Typewriter.php` - two lines
that put the stylesheet and the script into the site's own bundles -,
`assets/typewriter.css`/`assets/typewriter.js` and an install unit that brings
one text fill, the label of the pause button. The changes per version are in
[CHANGELOG.md](CHANGELOG.md).

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
another 300 ms, and types the next one. After the last line it **stops**: the
last line stays, complete, and its cursor is gone, so nothing blinks on forever.
The earlier lines have faded out - their text stays readable to a screen reader
through the `.nino-typewriter-reader` spans - so a three-line headline ends on
its last line alone, and a one-line typewriter types once and stays. Every one
of those numbers is an attribute away, and `data-typewriter-loop="1"` starts it
over instead.

**Existing pages.** A typewriter without `data-typewriter-loop` looped until
now and now types once; a three-line headline ends on its last line. The way
back is `data-typewriter-loop="1"` on the container. No JavaScript and reduced
motion are unchanged.

## The data attributes

All of them are optional, all of them are read off the container itself, and
an unreadable or negative value keeps the default rather than timing the
animation with a `NaN` - a selector the browser refuses included: it costs that
one attribute, never the other typewriters on the page.

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
| `data-typewriter-loop` | off | `1`, `true`, `on` or `yes` (any other non-empty value, `0`, `false`, `off` and `no` apart; surrounding spaces do not count) starts over after the last line instead of stopping |
| `data-typewriter-toggle` | none | A button after the container that pauses the typing and takes it up again; the value is its label. See *Pause, stop, hide* below |
| `data-typewriter-cursor` | `\|` | The cursor character; empty (`data-typewriter-cursor=""`) leaves it out |

A typewriter that writes its claim slowly, erasing as it goes, and stops on the
last line:

```html
<div class="nino-typewriter" data-typewriter-exit="backspace" data-typewriter-speed="70"
	data-typewriter-hold="900" data-typewriter-cursor="_">
	<p>Handmade in Munich.</p>
	<p>Since 1998.</p>
</div>
```

One that starts over after the last line, with the pause button:

```html
<div class="nino-typewriter" data-typewriter-loop="1" data-typewriter-toggle="[[/feature/typewriter/pause/label]]">
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

## Pause, stop, hide (WCAG 2.2.2)

Success criterion 2.2.2 asks for a way to pause, stop or hide anything that
moves for more than five seconds. A typewriter that **loops** is that, and a
single pass that takes longer than five seconds is too. Two things come with
the feature:

- **It stops.** The default is a single pass, so a typewriter that is left
  alone ends on its last line.
- **A pause button, on request.** `data-typewriter-toggle="<label>"` puts a
  button after the container - the container's next sibling, never inside it: a
  heading used as a typewriter would take the button into its own accessible
  name. A press pauses the typing (`aria-pressed="true"`, and `nino-is-paused`
  on the container, which also holds the cursor's blinking); the next press
  takes it up again, with the full delay of the step that was waiting. The
  button is removed - not hidden, because `.nino-btn`'s own display rule beats
  the `[hidden]` attribute - once nothing moves any more: after the last line of
  a typewriter that does not loop, and a button that has the keyboard focus
  then hands it to the container (`tabindex="-1"`). For one that does loop, it
  stays.

No button is drawn when the label is empty or still holds a fill nobody
resolved (`[[`), and none under `prefers-reduced-motion`, where nothing moves.

A pause button is **opt-in**: a typewriter that needs one to conform asks for
it. The button wears the kernel's own `nino-btn` classes; this feature adds only
its pressed state.

## Screen readers, no JavaScript, reduced motion

The typed text and the cursor are `aria-hidden`; beside them, each line keeps
its own complete text once in a clipped `.nino-typewriter-reader` span. A
screen reader therefore reads the lines the way the markup has them - all of
them, in order, once - instead of one character at a time.

Every rule in `typewriter.css` hangs off a class the script writes when it
takes over — `.nino-typewriter-line` on the lines, `.nino-typewriter-rest`,
`-cursor` and `-reader` on the spans it builds inside them. So wherever the
script does not run, not one of them matches and the container is exactly
what it reads like in the markup - paragraphs below one another:

- **without JavaScript**, and
- **for a visitor who asked for reduced motion**: `typewriter.js` checks
  `prefers-reduced-motion: reduce` before it changes anything and, when it
  is set, leaves the markup alone entirely. There is no attribute to
  override that.

## The words

The label of the pause button is a text fill, handed to the script as the value
of `data-typewriter-toggle`.

| Fill | English | Deutsch |
| --- | --- | --- |
| `[[/feature/typewriter/pause/label]]` | Pause animation | Animation pausieren |

The install unit merges it into the project's own `text/<locale>.php` at
activation, for every available locale and add-only, so a label a project
already wrote is kept; from then on it is the project's. A locale added later
needs the key by hand. The fill goes into the page as it stands, so a label must
not contain a double quote.

## Asset bundling

`typewriter.css`/`typewriter.js` reach the browser the same way the kernel
ships its own `Nino.css`/`Nino.js`/`Nino.ui.js`: `init()` calls
`\Nino\Html::addAsset()` to add them to the project's **own**
`/.cache/style.css` and `/.cache/script.js` bundles - the exact targets the
base install's `html-header.tpl`/`html-footer.tpl` already load on every page
via `[assets /.cache/style.css]`/`[assets /.cache/script.js]` - rather than a
bundle of this feature's own. A typewriter is written into whatever page
wants one, and every one of them already loads those two.

The asset paths are `/features/Typewriter/assets/...`:
`\Nino\Filesystem::path()` resolves `/features/...` against the features
directory, wherever `NINO_FEATURES_DIR` put it.

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
activation recording the class and the version and merging the label's text
fill, add-only, without writing a single file into the project, the real
`\Nino\Html::addAsset()`/`[assets ...]` bundling end to end (the generated
`/.cache/style.css`/`script.js` genuinely carry this feature's files), the two
promises the files themselves make - every rule bound to the class the script
writes, the rest span and the zero-width cursor - and deactivation. It loads
Nino's `tests/harness.php` from the checkout three levels up, where the feature sits in a project, or from the
one `NINO_ROOT` names:

```bash
php features/Typewriter/tests/typewriter-smoke.php
NINO_ROOT=../nino php features/Typewriter/tests/typewriter-smoke.php
```

`tests/typewriter-js-smoke.js` is the behaviour half, and the bigger of the
two: `typewriter.js` evaluated against DOM stand-ins and a clock the test
moves itself, measuring the whole sequence - the viewport start, the single
pass and the loop, the pause button, every data attribute, the values it
refuses, the emoji it does not cut in half, the markup it leaves for a screen
reader and the markup it leaves alone under reduced motion.
`typewriter-smoke.php` runs it through `node` where node is on the path, and
says so when it is not; it also runs on its own:

```bash
node features/Typewriter/tests/typewriter-js-smoke.js
```

# Design

**Key:** `design` · **Class:** `\Nino\Modules\Design` · **Version:** 0.1.0 · **Nino:** `^1.4`

The look of a site, chosen per part of a page rather than per page, and
compiled into the one stylesheet the css bundle already names:
`assets/theme.css`.

Up to Nino 1.1 the setup wizard asked four questions about the look and
compiled the answers into a whole-page theme. Nino 1.2 does not ask: the base
install unit delivers one fixed `assets/theme.css`, and every project starts
from the same page. This feature is what changes it afterwards - and it does
not offer themes. It offers a **set per part**, so that loud section titles
from one design and round buttons from another is a thing you can have rather
than a thing you argue yourself out of.

> **0.1.0 is the first cut.** The setup store, the compiler, the library, the
> panel and the tests are here, and the library is a choice rather than a
> placeholder: ten headers, eleven footers and five sets for each of the seven
> set parts. `v1` of a set part is the starting point rather than a design -
> the framework's own values, every one of them as a triple (see
> [The library](#the-library)) - and the four beside it are designs.

## The nine parts

| Part | Kind | What it owns |
| --- | --- | --- |
| `header` | frame | The page bar - markup and css |
| `footer` | frame | The page foot - markup and css |
| `atf` | set | The hero above the fold: title, subtitle, the arrow down |
| `section` | set | Section title, subtitle, text, padding, margin, border |
| `article` | set | Article cards and their grid: image, title, meta, excerpt |
| `buttons` | set | Every `.nino-btn` variant, and the link that looks like one |
| `forms` | set | Fields, labels, help text, validation states |
| `lists` | set | Lists, tables, accordions, the definition rows |
| `blocks` | set | Pricing rows, timelines, logo bars, callouts, quotes |

Two kinds, because two of them are different in a way worth naming: a **frame**
brings markup with it (`template.tpl`, which becomes the project's
`frame-header.tpl` / `frame-footer.tpl`) and a stylesheet beside it. A **set**
is one stylesheet and nothing else - it changes how something already on the
page looks, and can never change what is on it.

The parts do not carve the page into disjoint boxes and are not meant to: an
article title is inside a section, and a button is inside both. What a part
owns is a list of classes, not a region. `Setup::PARTS` is that list, in the
order the cascade wants the parts concatenated in.

## The panel

**Design**, in the workbench's **Features** group; one permission,
`/_admin/design/manage`. One screen, **one part at a time**: the picker says
which one is open - the nine parts and `Global` - and everything below it
belongs to that one.

Its two halves, **Structure** and **Colours** (below), are the tabs beside the
panel's name in the head the workbench draws over every pane. They switch the
column of controls; the preview beside it stays the same page on both.

| | |
| --- | --- |
| **Part** and **Variant** | two fields of one card: which part is open, and which set it is given, listed as `v3 - Floating bar` out of the file's own name and `@name`. Each carries the terse line about it under the control - a variant's `@description`, the root size's sentence about percentages. `Global` shows the root size instead of a variant |
| **Finetuning** | a block of its own under the card, opened by a small heading and one line saying what its rows decide. One row per knob the chosen variant answers to, at −1 / 0 / +1, each row named and noted the way the kernel's own Design module named it. `Global` has the position every part follows |

The part and the variant are two questions of the same kind - what is being
designed, and what it is being given - so they stand side by side in one field
grid rather than one above the other with a card between them. Everything that
answers to the part named on the left is under that card.

Nine rows at once is a list to read; one part is a decision to make. And the
preview beside it is the whole page either way, so switching parts never means
losing sight of what the last one did.

The whole screen is built the way the **section composer** of the Templates
feature is built - a card of labelled fields, a titled block of rows under it,
a sticky preview pane beside them - because they ask the same kind of question
and a workbench where two screens answer it two different ways is a workbench
somebody has to learn twice. What the two do not share is a stylesheet: the
composer's `pd-*` names belong to that feature, and the measures here are
rebuilt out of the design system's own tokens under `design-*` names.

A Finetuning row **follows the level above it until somebody moves it**, and
says so by being drawn quietly: what is on screen is the value that will
compile either way, so a row nobody has touched is not a row with no answer -
it is one whose answer is still somebody else's. Moving it makes it its own and
puts the way back (`↺`) beside it.

Choosing and applying are two actions on purpose. **Save draft** writes
`data/design.php` and nothing else; **Apply to website** writes it and then
produces `assets/theme.css` and the two frame templates. A draft is not a
website, and the two buttons carry one line under them saying so. The screen
says when the two have drifted apart rather than hiding it behind an autosave:

| | |
| --- | --- |
| *The file answers to this selection.* | applied, and nothing has changed since |
| *The draft is saved but not applied* | the selection is on disk, the site still shows the previous one |
| *Never applied* | there is no `assets/theme.css` of ours yet |
| *Not written by Design* | the delivered file, or one somebody edited - any of the three files, see below |

That table stands at the **bottom** of the controls, under *Applied file*.
It is true and worth saying, and it is not what somebody opening this screen
came to find out - and only a line that is something to act on is marked. The
first row used to be a green panel, and a green panel is a thing the eye keeps
checking, while that line says nothing at all about the selection on screen.

What *is* about the selection on screen is **Reset**, which appears at the far
left of the action bar the moment the screen stops being the stored selection
and goes again when it is back. It goes back to what was saved and never
further: what was saved is saved, and a button that quietly returned a project
to the delivered design would be a much larger promise.

### Applying asks first

The first apply in a project is the normal one to be careful about: its
`assets/theme.css` and its two frame templates are the wizard's until this
feature takes them over, and a later one may meet a header somebody added a
shortcode to. So **every** apply asks, after saving the draft and before
writing anything. `design/plan` reads what would happen to each of the three
files, and the confirmation lists it, one fact to a line:

- which files are rewritten - always the three;
- which of them Design did not write (`foreign`, the delivered file, or
  `edited`, one it wrote and somebody changed) and that they are replaced;
- which **shortcodes** in a frame are lost - the whole token, such as
  `[consent-settings]` or `[template /templates/html-footer-nav]`, that is in
  the file on disk and not in the variant that replaces it. `[[fills]]` are not
  compared; the line says to put it back by hand afterwards;
- that what is there now is kept first as the previous version, and the date
  of the one it replaces - said only where something would change.

Cancelling leaves the draft saved and the website as it is. Confirming sends
the apply with `force` where a file was not Design's - including a frame that
was only edited beside a stylesheet that is still ours, which used to be a
`409` with nothing on screen to answer to. The result says it applied, that
files Design had not written were replaced, and which shortcodes to add again.

### The previous version

One slot, `data/design-previous.php`: the three files as they were and the
setup they were written under, kept in one atomic write before an apply
replaces them. "Written under" is meant exactly: the panel saves the draft
before it applies, so `data/design.php` already holds the new choices when the
apply takes its snapshot. An apply therefore records the choices it compiled
from in `compiled.setup`, beside `at`, `sha` and `input`, and the slot keeps
those with their record - not the draft. A record from before that has no
setup: where its `sha` still names the stylesheet on disk, the slot keeps
`data/design.php` whole with it, draft included, since the draft cannot be told
from the choices, and a restore of it reads *saved, not applied*. For files that
no apply of Design's wrote (the delivered ones, or a stylesheet that is no
longer the one the record names) the slot holds no setup at all. One rather
than a history, because "the version before" is what the panel promises - a
list would need ids, pruning and a screen to choose from. If a write of an
apply fails, the files it had already written are put back from memory - a
frame that was not there is removed again, so the stylesheet never stays over
frames it was not drawn against - and the slot is as it was: the one it replaced
written again, or none where the apply had made one for a change that did not
happen (`500`). If the slot cannot be written the apply is refused and nothing
is overwritten. An apply that would write bytes identical to every file already
there keeps nothing, so a second apply of the same thing never replaces the
previous version with a copy of the present.

**Restore previous version** (state box, with the date) asks, then swaps the
slot with the present: the files are written from it, `data/design.php` goes
back with them - the choices, size and colours the files were written under,
with the record of what was compiled, so the panel reads that version as
*current* - and what was there becomes the slot, exactly as it was (a saved
draft included), so restoring can be undone by restoring again. The panel is
loaded from the server afterwards, so the confirmation says that unsaved edits
on screen are lost. A file the slot did not have is left alone, never deleted:
the page includes the frames. If a write fails the files already replaced are
put back from memory and the slot is left as it was (`500`); with no slot it is
a `404`. If only the new slot cannot be written after the files are back, the
answer is still `200`, with a note, because the site has changed. A slot with
no setup (the files were not Design's) brings back no record of what was
compiled and leaves the choices on screen as they are; the restored files are
the delivered ones, so the panel reads *Not written by Design* rather than
*current*. An apply that cannot keep the previous version or cannot write a
file is a `500`, a refused one a `409`.

### The preview

Beside the selects is what they mean: the specimen page, rendered against
**this** project - its menu, its logo, its social links, its fonts - under
the stylesheet the current selection compiles to. It follows every select, and
it shows the selection **on screen**, not the one on disk: looking is what a
person does while deciding, and a preview that could only show a saved decision
would make saving the way to ask a question. Nothing about it is written
anywhere.

The frame **follows the picker**: opening *Blocks* puts the pricing row on
screen, opening *Footer* the footer, and *Global* the top of the page - because
hunting for the part you just opened is work the screen can do. The switch
beside the width turns it off for a visit; a knob move never jumps, only
changing the part does. It scrolls the frame's own window and nothing else -
`scrollIntoView()` walks every scrollable ancestor of an element, and inside a
same-origin iframe the workbench's own pane is one of them, so the column
beside the frame slid away under the selects that had just been used.

Under the frame the selection is written out in words: the part, its variant,
the root size, where each knob of the open part stands, the two colours and the
palette knobs while the Colours tab is open, and what `assets/theme.css`
currently is. A picture says what a design looks like and nothing at all about
which selection produced it - two sets a step apart are a comparison somebody
has to make from memory - so the list is the other half of the answer, and it
follows every control on the left.

A **width** picks phone, tablet or desktop. The frame renders at that width and
is scaled into whatever the column has room for, so a desktop layout stays a
desktop layout in half a pane rather than becoming the phone view with the
wrong label - and a header set's own answer to "where does the menu go when
there is no room" is visible at all three.

Two things are worth knowing about how it is delivered. Seven of the nine parts
are a stylesheet and nothing else, so changing one swaps a single `<style>`
inside the frame that is already standing - no reload, no jump back to the top.
Header and footer bring markup, so those rebuild the document. And the
framework under it (`_nino/Nino.css` plus the two scripts) is not inlined but
bundled into `_admin/.cache/design-preview.{css,js}` beside the workbench's own
two, because the workbench sends a `Content-Security-Policy` with
`script-src 'self'` that an inline `<script>` in the frame would fall foul of -
and because the half that never changes has no business travelling with every
preview. The workbench already needs `_admin/.cache/` writable; this needs
nothing beyond that.

## The library

`library/` holds what a project can choose from, and the feature ships it:

```
library/
  base.css              the token and role layer, always first
  header/v1 … v10/      template.tpl + style.css
  footer/v1 … v11/      template.tpl + style.css
  sets/<part>/v1 … v5   one file per choice, per part
```

`base.css` is sections 1 and 2 of the `theme.css` the wizard delivers, byte for
byte - the whole `--nino-*` palette and raster, and the roles they are assigned
to (`--color-title`, `--color-section-dark-bg`, `--text-4`, …). The feature
carries its own copy because a project deletes `_admin/install/` once setup is
done: the installer library is gone by the time anything here recompiles.
`tests/design-smoke.php` holds the two files to each other wherever a checkout
still has the installer, so they cannot drift into *installing Design silently
changes how the site looks*.

Every library file carries its own name and description in its opening
comment, and that is what the panel lists it as:

```css
/*	Nino Design - header v3
 *
 *	@name				Floating bar
 *	@description	A rounded, slightly translucent bar with air around it,
 *								capped at 92rem. Reads as current rather than as a frame.
 */
```

A file without a `@name` is offered under its own file name, which is what a
set in progress looks like. The tags live in the file rather than in a manifest
beside it: a set is one file, and a second file per set is a second file to
keep in sync.

Every part ships `v1`, which is the starting point rather than a design: what
it declares is the framework's own values, and every one of them as a triple,
so a fresh compile renders Nino exactly as it is and the knob has something to
reach before a set has been written. Beside that it carries every other rule
`Nino.css` sets for that part, commented out with today's values - the handles
that part has, in one place. A new set starts as a copy of `v1`.

Four more per part stand beside it, and they are meant to be edited rather than
only chosen - each is one decision carried through, so changing that decision is
a change in one place:

| Part | | | | |
| --- | --- | --- | --- | --- |
| `header` | v7 Two decks | v8 Pill menu | v9 Quiet caps | v10 Slim bar |
| `footer` | v8 Centred stack | v9 Sitemap | v10 Dark slab | v11 Hairline |
| `atf` | v2 Full height | v3 Quiet opening | v4 Display opening | v5 Left rail |
| `section` | v2 Wide bands | v3 Tight editorial | v4 Display titles | v5 Framed bands |
| `article` | v2 Flat cards | v3 Soft cards | v4 Text first | v5 Compact rows |
| `buttons` | v2 Pills | v3 Square caps | v4 Soft keys | v5 Compact |
| `forms` | v2 Boxed fields | v3 Underlined fields | v4 Filled fields | v5 Dense |
| `lists` | v2 Ruled | v3 Roomy | v4 Dense data | v5 Quiet marks |
| `blocks` | v2 Bordered plans | v3 Elevated plans | v4 Tinted panels | v5 Compact steps |

They do not carry `v1`'s commented-out reference block: that is the same in
every variant of a part, and repeating it five times would be five copies to
keep in step. Read `v1` for the handles, the variant for what was done with
them.

Several of them declare knobs `v1` does not, which is how a knob reaches a part
that never had one - `measure` for the column a wide band is read in, `volume`
for the size of a button's own label. `tests/design-smoke.php` holds every file
in the library to the vocabulary: a knob outside `Setup::KNOBS`, a triple with
a step missing, a rule reading a token the file never declared and a variant
without a name or a description each fail it.

### Writing a set

Two rules, and they are what keeps nine sets from turning into one:

- **Only the classes of this part.** A button set does not touch a section
  title, however tempting. Nothing enforces it; the reason to keep it is that
  the moment a set reaches outside itself, choosing sets stops composing.
- **Colours through the roles, never as literals** - `var(--color-title)`,
  `var(--color-primary)`, `var(--color-section-tint-bg)`. Sizes are absolute
  (`2rem`), because the root size already scales the whole page.

A third thing is not a rule but is worth doing: declare the three steps of the
knobs the set answers to, with `--default` where the framework already stands
(see [The finetune knob](#the-finetune-knob)). That is the whole of publishing a
knob - the panel lists what the file declares and nothing else. A `@knob` line in
the opening comment beside `@name` is documentation for whoever reads the file;
the panel reads the declarations.

### Writing a frame

A frame is a `template.tpl` and a `style.css` in `library/<part>/<name>/`. The
template is what the project's `frame-header.tpl` / `frame-footer.tpl` becomes,
included by `html-header.tpl` through `[template /templates/frame-header]`, so
it goes through `\Nino\Html::renderHtml()` and may use textfills, `[template]`
includes and shortcodes - `[navigation]` in particular.

Two things a header frame has to keep, whichever shape it is:

- The bar carries `nino-scroll-header`. `Nino.css` hides it under
  `body.nino-scroll-down` by taking back `max-height`, `min-height`, both
  vertical paddings and both horizontal border widths - so a frame must not
  give the bar a plain `height`, which none of that can take back. A frame
  that is not a bar opts out where it says so: `v6`'s rail hands
  `max-height: none` back above its own breakpoint.
- `header/v7` and `footer/v2`, `v8` and `v10` include
  `[template /templates/social-links]`, the template the catalogue's Social
  links feature installs, holding its `[social]`. Without that feature the
  include names a template the project does not have, which renders as
  nothing - so a frame never names the shortcode itself, which would stand on
  the page as text wherever the feature is not there.

A frame draws the logo from the kernel's slot, as the base unit's own frame
does: `[image /logo alt=""]` in the header, and in a footer, where the picture
carries a class of its own, the same shortcode around the `<img>` the frame
wants - `[image /logo alt=""]<img src="[[src]]" width="[[width]]" height="[[height]]" class="nino-footer-logo" alt="[[alt]]">[/image]`.
A frame never names `images/logo.png` or `images/logo-invert.png`: a project
that uploaded its logo in the Images panel has neither file, and with no logo
uploaded yet the slot renders nothing, not a broken picture. There is **one slot
for both variants**. A frame drawn for a dark ground says so with the modifier
class `nino-logo--invert` on the wrapper (on the picture itself in a footer,
which has no wrapper) and the library applies no CSS filter to it - a project
that needs a lighter or darker mark uploads one that is, or styles the class.

Every footer frame shows the link to the imprint and the privacy policy the
same way: it outputs the project's menu `legal` with
`[navigation nav="legal" id="legal__nav"][/navigation]` - the menu the Legal
module of Nino 1.4 has the setup wizard create with both pages, and the
Navigations panel keeps - where it used to include `html-footer-legal`, a
template that is no longer delivered. The `id` is there because the fragment
would otherwise write `id=""`. The menu is a child of the same container the
single link was; a frame that includes `html-footer-legal` again links nothing.

## The finetune knob

The knobs are **Nino's own** - the ones the kernel's Design module published as
its raster group before the look left the core, minus the one that is the root
size here:

| | | |
| --- | --- | --- |
| **Headings** | how far they grow | Calm · Standard · Bold |
| **Spacing** | gaps and line height | Tight · Standard · Airy |
| **Corners** | how round | Sharp · Standard · Round |
| **Width** | how wide content runs | Narrow · Standard · Wide |

Fixed rather than per set, and that is the whole point: "Spacing" means the same
thing on a section as on a form, so moving it globally means something. A set
that invented its own vocabulary would give every part a private language and
the global position nothing to be the position of.

A set answers to a knob by declaring its three steps under the part's and the
knob's name:

```css
:root {
	--section-spacing--less: 		var(--space-3);
	--section-spacing--default: var(--space-4);
	--section-spacing--more: 		var(--space-5);
}
.nino-section { padding: var(--section-spacing) 0; }
.nino-section-text { margin-bottom: calc( var(--section-spacing) /3 ); }
```

**Declaring the triple is publishing the knob.** The panel offers exactly what a
set declares, so a handle the stylesheet does not answer to can never be
offered - and a knob moves a family of values together, which is what makes it
one knob. "Spacing" that moved the band but not the paragraph under it would be
two knobs wearing one name.

The knob never computes. `+1rem` behaves differently on a default of `5rem` than
on `2rem`, and plenty of scales are not linear at all - so a set declares what
its three steps *are*, and the knob picks one. **`--default` is the value the
framework uses today**, in every set the library ships: a knob nobody has moved
compiles to the page that was already there, which is what makes a set adoptable
at all. The compiler gathers the picks into a single block at the end of the
sheet, one line per part and knob:

```css
/* ==== 12. the knob positions ==== */
:root {
	--buttons-shaping: var(--buttons-shaping--less);
	--section-spacing: var(--section-spacing--more);
	--section-volume: var(--section-volume--more);
}
```

That block exists because css cannot compose a variable name. It also makes the
whole knob state one readable thing: a project that removed the feature can
still move a knob by editing one line.

### Two levels

A knob has a **global** position, and a part may be moved away from it - every
part that was not keeps following, and keeps following when the global one
moves. That is a different state from a part that happens to name today's value,
and the reason the setup stores only the decisions that were actually made.

`Global` lists the knobs *any* chosen set answers to; a part lists the ones its
own set does. A knob nothing follows is a knob worth not offering.

## The root size

`s`, `m` or `l`, compiled as the percentage pair `Nino.css` reads:

```css
:root { --nino-base-size: 100%; }
@media (min-width: 768px) { :root { --nino-base-size: 112.5%; } }
```

| | below 768px | from 768px | at a 16px browser default |
| --- | --- | --- | --- |
| `s` | `87.5%` | `93.75%` | 14 / 15px |
| `m` | `100%` | `112.5%` | 16 / 18px |
| `l` | `112.5%` | `131.25%` | 18 / 21px |

A full step apart rather than a hair, so the choice is one somebody can see on
the page instead of measure. `m` is the delivered size and does not move.

A percentage of the visitor's browser default, never a `px` length - `html`'s
`font-size` is the only place `--base-size` is read, so one pair scales every
`rem` on the page and nothing else has to know.

## Colours

The second tab, and the other half of a design: the structure decides which set
a part is on, the palette decides what every one of those sets is drawn in.

Two colours and five knobs.

- **Brand colour**, used *exactly* as picked.
- **Second colour** - one row, because where it comes from and what it is are
  one question. Four automatic positions (Monochrome, Analogous, Triadic,
  Complementary) carry the brand's own lightness and chroma round the wheel;
  the swatch at the end of the row shows the colour that came out and is drawn
  quietly to say nobody chose it. Open it and that hex is used exactly as
  picked - no position is lit any more, because a hex overrides the knob
  entirely - and `↺` beside it hands the question back to the wheel.
- **Temperature** - which hue the greys lean on. At *Brand* they carry a trace
  of the brand itself, at *Neutral* no colour at all.
- **Saturation** - how much colour every surface carries, not only the brand.
- **Contrast** - how hard the type reads. Every position clears WCAG AA; the
  knob decides how far above it the ink sits.
- **Depth** - how far a panel separates from the page: the alternate surface,
  the borders and the shadows move together.

The three scales go further at their outer positions than the first cut did;
the middle one is the framework itself and has not moved, which is what keeps
an untouched project byte-identical:

| | at the first position | at the third |
| --- | --- | --- |
| **Saturation** | *Muted*: a seventh of the chroma on every surface, the greys included - brand-safe surfaces fall from about 0.064 to 0.021 | *Rich*: more chroma in links, focus ring and the grey tint. The brand-safe surfaces gain only a little - a surface solved to 4.5:1 against its ink has little room for chroma, and the sRGB gamut ends there |
| **Contrast** | *Soft*: a dimmer ink, body text about 10:1 on white instead of 13.7:1; brand surfaces get darker, because the dimmer light ink needs a darker ground - and so does the scrim over a cover photograph, 83% in light and 80% in dark mode instead of 76% and 73%, which is darker than *Strong* leaves it | *Strong*: text, links and surfaces solved to 10:1 and muted text to 9:1. Only a few brand colours reach that on their own, so the note under the brand swatch shows for most. The scrim over a cover photograph is solved to 7:1 and stays where it was (80%) |
| **Depth** | *Flat*: the alternate band keeps a trace (about 1.02:1 against the page) and the light shadow is gone | *Raised*: a border asks 7:1 and a shadow falls at up to 95%. The 7:1 border is reached on `default`, `alt`, `tint`, `dark` and `black` only - a brand, accent or status surface is solved to about 4.5:1 against its ink and a line on it stops near 4.6:1 |

None of it takes a floor away: the suite holds text, muted text, links, the
focus ring and borders to their ratios at every position of the three, in both
modes, on four unlike primaries. A project compiled before this with one of the
three off *Standard* reads *saved, not applied* once, because
`Colours::REVISION` joins the fingerprint of exactly those setups; applying
again brings the new steps, and one with all three on *Standard* is not asked.

Out of that comes every surface a look bands with - `default`, `alt`, `tint`,
`dark`, `black`, the four brand roles and the three status ones - and, for each,
everything that has to be readable on it: the ink, the muted ink, the link, the
border, the focus ring, hover, active, disabled and a shadow.

**The promise is measured, not assumed.** Colours are solved in OKLCH, whose
lightness is perceptual, so a hue can be moved onto a contrast target without
changing what colour it reads as. Every emitted pair is then checked with the
real WCAG formula; the suite holds all of them to 4.5:1 in both modes.

Two surfaces are deliberately outside that promise and say so: `brand` and
`accent` are the colours the picker returned, byte for byte, so there is no
lightness left to solve with. That is what `brand-safe` and `accent-safe` are
for, and it is what a look writes on. Where the brand as picked does not clear
the target by itself, the panel says so under the swatch rather than quietly
moving the colour.

**Nothing moves until you move it.** With the knobs where they start, the solver
lands on the framework's own palette to the byte - the same values `base.css`
declares as a static default. A project that never opens this tab compiles to
the colours it already had.

Light and dark come out of the same settings. The generated block writes the
light palette to `:root`, and the dark one twice: once inside
`@media (prefers-color-scheme: dark)` for the reader who has chosen nothing, and
once on `:root[data-nino-mode="dark"]` for the one who has.

The maths is the kernel's own Design module, which shipped this until the look
left the core in 1.2. It was measured against the framework's colours, and there
was no reason to re-derive it - what did not come along is the size raster,
which the library's part sets and their knobs now own.

## What it compiles

`Compiler::compile()` concatenates, in this order:

1. `base.css` - tokens and roles
2. the palette
3. the root size pair
4. … the chosen frames, then the chosen sets, in `PARTS` order
5. last, the knob positions

and puts a header on top naming what was chosen, plus the sha256 of everything
below it.

That header is what makes overwriting safe. `assets/theme.css` is explicitly a
file you may edit by hand, so the first compile in a project will usually meet
one that somebody means to keep. `Compiler::write()` refuses a file whose
header does not claim a digest that still matches its own body - the delivered
file, or one that was edited since - and says so, rather than replacing it.
`$force` is the way past it, and `Design::apply()` passes it through so the
decision belongs to whoever is looking at the screen - after the confirmation
described under [Applying asks first](#applying-asks-first).

`apply()` writes nothing before it has asked about all three files, and keeps
the version before them (see [The previous version](#the-previous-version))
between that and the first write. `Compiler::frame()` is the exact bytes of a
frame as `writeFrame()` puts them, which is what lets `apply()` tell whether
an apply would change anything, and `Compiler::ownership()` tells `ours` from
`edited` (a stamp whose digest no longer matches) and `foreign` (no stamp).

## Data

`data/design.php` and `data/design-previous.php`, both declared under `data` in
`feature.php`, so `\Nino\Backup::manifest()` carries them. The second is the
previous version (see above): a php array with `at`, the setup, and the bytes of
the three files, `null` for one that was not there. A backup restore may simply
replace it - nothing needs merging.

`data/design.php` holds what was chosen and nothing derived from it: the set per
part and the knobs that part was moved at, the global position of every knob,
the root size, the palette's two colours and five knobs, and what was last
compiled: when, the digest of the stylesheet, the fingerprint of its input and
the choices themselves (`compiled.setup`, which the previous version needs). It is the only thing here that cannot be worked out
again from what is on disk, and it is deliberately outlived by the stylesheet.
Removing the feature leaves `assets/theme.css` working - it is an ordinary file
the bundle already points at - and leaves the setup beside it. Installing the
feature again finds the setup and carries on rather than starting over.

Every value is normalised against the library that is actually present:
`Setup::normalize()` replaces a part naming a set that is not there with the
first one that is, and says which, rather than compiling a sheet with a hole
in it. A set name out of a stored file is input, so it is held to
`[A-Za-z0-9][A-Za-z0-9._-]*` before it is ever joined to a path.

## Authoring sets

A set is authored in the panel's own preview. `Preview` is where the
specimen, the frames around it and the compile live, and the panel renders
it against this project - its menu, its logo, its social links, its
fonts - so a set looks the same while it is being designed as it will on the
site. Edit a set under `library/sets/<part>/`, pick it in the panel, and the
preview follows the picker; a knob or a step that did not resolve is named in
the bar rather than passed over in silence.

The whole-page themes Nino's setup wizard offered up to 1.1 are gone from this
repository, on purpose: a project composes its look from the part sets rather
than starting from a theme. A presets field in the panel, combining part
presets with modifications, may come later.
## Tests

`tests/design-smoke.php` covers the manifest and activation through
`\Nino\Features`, the library coverage per part, the traversal refusals,
normalisation and step resolution, what the compiler emits and in which order,
the cross-repo comparison of `base.css` against the delivered `theme.css`, that
every footer frame outputs the menu `legal` once and none includes
`html-footer-legal`,
`write()`'s refusal and its `$force` for the stylesheet **and** for the frame
templates, the names and descriptions read out of the library files, and the
panel's six actions - what it lists, that saving stores without compiling,
that applying is a `409` over a file that is not ours, and both refusal codes
(`401` without a session, `403` without the permission) for every one of them.

For the previous version and the question before applying: that `plan` lists
the three delivered files as foreign with the hand-added shortcode of the
footer as lost; that an apply after it holds the old bytes of all three files
and, where they were Design's, the setup they were written under; that applying
again with nothing changed leaves the slot untouched; that a restore writes the
files back byte for byte, brings the setup with them and leaves the slot holding
what was applied, so a second restore returns; that this holds for the panel's
own sequence - save, plan, apply, save, plan, apply, restore - where the first
version comes back with its choices, size and colours and reads *current*;
that a restore over a record written before it carried its setup reads *saved,
not applied*; that a restore without a slot is a `404`, one whose write fails
is a `500` that puts back what it had replaced and leaves the slot unchanged,
and one the Csrf check already failed writes nothing; that an apply whose snapshot
cannot be written is a `500` with every file unchanged; that a frame which is
only edited beside a stylesheet that is ours is planned as `edited` and goes
through with `force`; that the fingerprint carries `Colours::REVISION` for a
colour knob off *Standard* and for no other setup; and that both text files hold
the same keys and every text the script names is one of them.

For a write the disk refuses half way - a directory where the footer, the header
or the stylesheet belongs: that the apply answers `500` and names the file, puts
the stylesheet and the other frame back byte for byte, leaves the previous
version as it was and no record of a compile that did not happen, leaves no slot
behind where there was none, takes away a frame it had only just made, and goes
through once the disk allows it.

For the colours: for Saturation, Contrast and Depth at every position, in both
modes, on `#4faae8`, `#8b1d3f`, `#facc15` and `#111827` - text 4.5:1 (10:1 at
Strong; links 9.98:1), muted text, the focus ring and borders held to their ratios, a
Raised border 7:1 on the neutral grounds, brand-safe chroma never falling as
Saturation rises, Depth 1 differing from 2 in band and shadow with Flat keeping
a visible trace, and the scrim at Strong staying at or under 80%.

For the preview it covers the specimen (no frame of its own, a section per
part, one data-uri picture and no route to fetch it from), that `markup()`
reads the chosen frames out of the library rather than off disk, that a frame
without a template is named instead of quietly left out, that the sheet
resolves the public prefix so the webfaces load, and - for the panel's action -
that it answers the posted selection rather than the stored one, that the
framework is linked from a bundle that really carries it, that a stylesheet-only
change sends no second document, and that previewing writes neither the setup
nor the stylesheet.

For the knob: that a set publishes one by declaring its triple and no other
way, that an example in a comment is documentation rather than a declaration,
that the global position is a position of whatever any chosen set follows, that
a position is stored only where it is one and only for a knob the set answers
to, that a part with none of its own follows the global one while a part that
names one does not, that the compiled sheet asks each knob where it stands for
that part and says so in its header, that a setup written before the knobs were
told apart keeps its position, and that every set in the library declares all
three steps for every knob it answers to.

`tests/design-js-smoke.js` covers the panel's own script over a dom stand-in:
that the part and the size are two fields of one grid with a name over each
control and its line under it, that the knob rows stand in a block a small
heading opens, and that the summary under the frame says the part, the variant,
the size, every knob's current value, the palette on the Colours tab and what
the compiled file is - after a knob is moved, after another part is opened and
after the other half of the design is switched to. Its pane carries the head
the workbench renders over every panel: the screen draws no heading of its own
under it, the Structure / Colours strip stands in the head beside the name - one
strip however often the screen is drawn - and a switch, by click or arrow key,
redraws the column and leaves the strip and the focus on it where they are;
without a head the strip opens the column instead. It also covers the two
buttons and the line under them, that applying sends save, then plan, then asks
with a text naming the three files, the ones that are not Design's and the lost
shortcode, that cancelling sends no apply, that `force` is set exactly when a
file is foreign or edited, and that the restore button appears with a previous
version, asks first and posts only on a yes. `design-smoke.php` runs it too
wherever `node` is on the path.

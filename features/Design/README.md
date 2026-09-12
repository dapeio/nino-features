# Design

**Key:** `design` · **Class:** `\Nino\Modules\Design` · **Version:** 0.1.0 · **Nino:** `^1.2`

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

> **0.1.0 is the feature without its part styles.** The setup store, the
> compiler, the library, the panel and the tests are here. What is thin is the
> catalogue: six headers and seven footers are real, but every part set is the
> empty `v1` that declares nothing. Writing those is the work this exists for.

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
`theme.header.tpl` / `theme.footer.tpl`) and a stylesheet beside it. A **set**
is one stylesheet and nothing else - it changes how something already on the
page looks, and can never change what is on it.

The parts do not carve the page into disjoint boxes and are not meant to: an
article title is inside a section, and a button is inside both. What a part
owns is a list of classes, not a region. `Setup::PARTS` is that list, in the
order the cascade wants the parts concatenated in.

## The panel

**Design**, in the workbench's **Features** group; one permission,
`/_admin/design/manage`. One screen: the two decisions that are about the whole
page, then a row per part with the variant it is given and - for a set - the
step it may deviate at. Each row says what the chosen variant is, out of the
variant's own `@name` and `@description`.

Choosing and compiling are two actions on purpose. **Save the selection**
writes `data/design.php` and nothing else; **Save and compile** writes it and
then produces `assets/theme.css` and the two frame templates. A decision is not
a stylesheet, and the screen says when the two have drifted apart rather than
hiding it behind an autosave:

| | |
| --- | --- |
| *The file answers to this selection.* | compiled, and nothing has changed since |
| *saved but not compiled* | the selection is on disk, the site still shows the previous one |
| *never compiled* | there is no `assets/theme.css` of ours yet |
| *not one of ours* | the delivered file, or one somebody edited - see below |

The last of those is the normal first run: a project's `assets/theme.css` is
the wizard's until this feature takes it over. The panel turns
`Compiler::write()`'s refusal into a question rather than an error - the button
reads **Take the file over and compile**, once, and says so in the activity
log. The same holds for the two frame templates.

## The library

`library/` holds what a project can choose from, and the feature ships it:

```
library/
  base.css            the token and role layer, always first
  header/v1 … v6/     template.tpl + style.css
  footer/v1 … v7/     template.tpl + style.css
  sets/<part>/v1.css  one file per choice, per part
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

Every part ships `v1`, which is deliberately empty: it declares nothing and
lets the framework's own rules stand, so a fresh compile renders Nino exactly
as it is. What it does carry is every rule `Nino.css` sets for that part,
commented out with today's values - the handles that part has, in one place.
A new set starts as a copy of `v1`.

### Writing a set

Two rules, and they are what keeps nine sets from turning into one:

- **Only the classes of this part.** A button set does not touch a section
  title, however tempting. Nothing enforces it; the reason to keep it is that
  the moment a set reaches outside itself, choosing sets stops composing.
- **Colours through the roles, never as literals** - `var(--color-title)`,
  `var(--color-primary)`, `var(--color-section-tint-bg)`. Sizes are absolute
  (`2rem`), because the root size already scales the whole page.

### Writing a frame

A frame is a `template.tpl` and a `style.css` in `library/<part>/<name>/`. The
template is what the project's `theme.header.tpl` / `theme.footer.tpl` becomes,
included by `html-header.tpl` through `[template /templates/theme.header]`, so
it goes through `\Nino\Html::renderHtml()` and may use textfills, `[template]`
includes and shortcodes - `[navigation]` in particular.

Two things a header frame has to keep, whichever shape it is:

- The bar carries `nino-scroll-header`. `Nino.css` hides it under
  `body.nino-scroll-down` by taking back `max-height`, `min-height`, both
  vertical paddings and both horizontal border widths - so a frame must not
  give the bar a plain `height`, which none of that can take back. A frame
  that is not a bar opts out where it says so: `v6`'s rail hands
  `max-height: none` back above its own breakpoint.
- `footer/v2` includes `[template /templates/html-socialmedia]`. That template
  is in the base install unit, so the include resolves in any project - a
  frame that needs it does not have to bring it.

## The finetune knob

A value the knob should reach declares its three steps rather than one value:

```css
:root {
	--section-title-fontsize--less:    1.6rem;
	--section-title-fontsize--default: 2rem;
	--section-title-fontsize--more:    2.6rem;
}
.nino-section-title { font-size: var(--section-title-fontsize); }
```

The knob never computes. `+1rem` behaves differently on a default of `5rem`
than on `2rem`, and plenty of scales are not linear at all - so a set declares
what its three steps *are*, and the knob picks one. The compiler gathers those
picks into a single block at the end of the sheet, one line per token:

```css
/* ==== 12. the knob positions ==== */
:root {
	--section-title-fontsize: var(--section-title-fontsize--default);
}
```

That block exists because css cannot compose a variable name. It also makes
the whole knob state one readable thing: a project that removed the feature can
still move a knob by editing one line.

The knob is global, and any part may deviate from it - "articles rounder",
"buttons squarer". A part that names no step of its own follows the global one
and keeps following it when it moves; that is what `step => null` means, and it
is a different state from a part that happens to name today's global value.

## The root size

`s`, `m` or `l`, compiled as the percentage pair `Nino.css` reads:

```css
:root { --nino-base-size: 100%; }
@media (min-width: 768px) { :root { --nino-base-size: 112.5%; } }
```

A percentage of the visitor's browser default, never a `px` length - `html`'s
`font-size` is the only place `--base-size` is read, so one pair scales every
`rem` on the page and nothing else has to know.

## What it compiles

`Compiler::compile()` concatenates, in this order:

1. `base.css` - tokens and roles
2. the root size pair
3. … the chosen frames, then the chosen sets, in `PARTS` order
4. last, the knob positions

and puts a header on top naming what was chosen, plus the sha256 of everything
below it.

That header is what makes overwriting safe. `assets/theme.css` is explicitly a
file you may edit by hand, so the first compile in a project will usually meet
one that somebody means to keep. `Compiler::write()` refuses a file whose
header does not claim a digest that still matches its own body - the delivered
file, or one that was edited since - and says so, rather than replacing it.
`$force` is the way past it, and `Design::apply()` passes it through so the
decision belongs to whoever is looking at the screen.

## Data

`data/design.php`, declared under `data` in `feature.php`, so
`\Nino\Backup::manifest()` carries it. It holds what was chosen and nothing
derived from it: the set and step per part, the global step, the root size, and
the fingerprint of what was last compiled.

It is the only thing here that cannot be worked out again from what is on disk,
and it is deliberately outlived by the stylesheet. Removing the feature leaves
`assets/theme.css` working - it is an ordinary file the bundle already points
at - and leaves the setup beside it. Installing the feature again finds the
setup and carries on rather than starting over.

Every value is normalised against the library that is actually present:
`Setup::normalize()` replaces a part naming a set that is not there with the
first one that is, and says which, rather than compiling a sheet with a hole
in it. A set name out of a stored file is input, so it is held to
`[A-Za-z0-9][A-Za-z0-9._-]*` before it is ever joined to a path.

## Designing against it

`design-library/preview.php` in this repository is a harness for authoring
sets - dev only, never deployed, and not part of the feature archive. It boots
a real Nino against a throwaway project, compiles through this feature's own
`Setup` and `Compiler`, and renders one specimen page that touches every class
a set can reach.

```bash
php -S 127.0.0.1:8080 design-library/preview.php
```

The array at the top of that file names one set per part. The throwaway
project is rebuilt on every request, so editing a set is a reload away, and
anything that did not resolve is named in the bar along the bottom rather than
passed over in silence.

## Tests

`tests/design-smoke.php` (77 checks) covers the manifest and activation through
`\Nino\Features`, the library coverage per part, the traversal refusals,
normalisation and step resolution, what the compiler emits and in which order,
the cross-repo comparison of `base.css` against the delivered `theme.css`,
`write()`'s refusal and its `$force` for the stylesheet **and** for the frame
templates, the names and descriptions read out of the library files, and the
panel's three actions - what it lists, that saving stores without compiling,
that compiling is a `409` over a file that is not ours, and both refusal codes
(`401` without a session, `403` without the permission).

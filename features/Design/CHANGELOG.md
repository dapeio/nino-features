# Changelog

All notable changes to the Design feature are documented in this file.
A release is the tag `design-<version>` of dapeio/nino-features.

## 0.1.0 — 2026-09-11

First cut: the setup store, the compiler, the library and the panel. What is
thin is the catalogue - six headers and seven footers are real, every part set
is the empty `v1` that declares nothing. Writing those is the work this exists
for, and the version says 0 for that reason.

### The panel

- **Design**, in the workbench's Features group, one permission
  `/_admin/design/manage`. The two decisions about the whole page, then a row
  per part: the variant, and - for a set - the step it may deviate at. Each row
  says what the chosen variant is.
- Choosing and compiling are two actions. **Save the selection** writes
  `data/design.php` and nothing else; **Save and compile** also produces
  `assets/theme.css` and the frame templates. The screen says when the two have
  drifted apart instead of hiding it behind an autosave.
- The first compile in a project meets the wizard's own `assets/theme.css`.
  `Compiler::write()`'s refusal becomes a question rather than an error: the
  button reads **Take the file over and compile**, and the activity log records
  that it was taken over.
- Every library file carries `@name` and `@description` in its opening comment,
  and that is what the panel lists it as. A file without a `@name` is offered
  under its own file name. `Setup::describe()` and `Setup::catalogue()` read
  them; the thirteen frames and the seven skeletons carry them.
- **A preview beside the selects**, in a frame, following every change: the
  specimen page rendered against this project - its menu, its logo, its fonts -
  under the stylesheet the current selection compiles to. It shows the
  selection on screen rather than the one on disk and writes nothing, so
  nothing has to be saved to be looked at. Phone, tablet and desktop widths,
  the frame rendered at that width and scaled into the column.
- A stylesheet-only change swaps one `<style>` inside the frame that is
  already standing; header and footer bring markup and rebuild the document.
  The framework under it is bundled into `_admin/.cache/design-preview.{css,js}`
  rather than inlined - the workbench's `Content-Security-Policy` refuses an
  inline `<script>` in that frame, and the half that never changes has no
  business travelling with every preview.

### The panel, rebuilt

- **One part at a time.** A picker at the top - the nine parts and `Global` -
  and everything below it belongs to that one: the variant with its description,
  and the knob under it. Nine rows at once was a list to read; one part is a
  decision to make, and the preview beside it is the whole page either way.
- **Finetuning is one row per knob**, not one step per part - and the knobs are
  Nino's own: Headings, Spacing, Corners and Width, the raster group the kernel's
  Design module published before the look left the core, with its labels, its
  notes and its three step words. Fixed rather than per set, because "Spacing"
  has to mean the same thing on a section as on a form for a global position to
  mean anything.
- A set answers to a knob by declaring `--<part>-<knob>--less/-default/-more`.
  Declaring the triple is publishing the knob, so a handle the stylesheet does
  not answer to cannot be offered; `Global` lists the knobs any chosen set
  answers to, a part the ones its own set does.
- Two levels: a knob's global position, and a part moved away from it. A row
  that has not been moved follows and is drawn quietly - what is on screen is the
  value that compiles either way - and moving it puts the way back (`↺`) beside
  it. Only decisions that were made are stored, so a part still following keeps
  following when the global position moves.
- Every set in the library now declares the framework's own values as triples,
  so the knob has something to reach before a set has been written. `--default`
  is what Nino.css uses today: a knob nobody moved compiles to the page that was
  already there - the same property the kernel's Design module built its scale
  on. A setup written before the knobs were told apart seeds every one of them
  with the single position it carried.
- **The frame follows the picker.** Opening a part puts it on screen: the seven
  sets have a section of their own in the specimen, a frame is the `<header>` or
  the `<footer>` around it, and `Global` is the top of the page. A switch beside
  the width turns it off; a knob move never jumps, only changing the part does.
- *How it is used* moved to the bottom of the controls. What `assets/theme.css`
  currently is, is true and worth saying, and it is not what somebody opening
  this screen came to find out. The preview lost its explanatory paragraph for
  the same reason - the frame under it is the explanation.
- The panel is called **Design** in German too, and the frame is scaled by the
  workbench's own `Nino.adminUi.scaleFrame()` rather than by a second copy of
  that arithmetic here.

### The palette

- **Colours**, the second tab - and the half of a design that was missing.
  Structure decides which set a part is on; the palette decides what every one
  of those sets is drawn in. Two colours and five knobs: Harmony, Temperature,
  Saturation, Contrast and Depth.

  Out of them comes every surface a look bands with - `default`, `alt`, `tint`,
  `dark`, `black`, four brand roles and three status ones - and, for each,
  everything that has to be readable on it: ink, muted ink, link, border, focus
  ring, hover, active, disabled, shadow. Light and dark from the same settings,
  the dark block written twice so the reader who chose nothing and the one who
  chose both get the right one.

  The promise is measured rather than assumed. Colours are solved in OKLCH,
  whose lightness is perceptual, so a hue can be moved onto a contrast target
  without changing what colour it reads as; every emitted pair is then checked
  with the real WCAG formula. `brand` and `accent` are the two deliberate
  exceptions - the colours the picker returned, byte for byte, with no lightness
  left to solve with - which is what `brand-safe` and `accent-safe` exist for,
  and the panel says so under the swatch where the picked colour does not clear
  the target by itself.

  **With nothing touched the solver lands on the framework's own palette to the
  byte** - all 121 declarations of `library/base.css`, light and dark. A project
  that never opens this tab compiles to the colours it already had, which is the
  property that makes the tab adoptable at all and the first thing the suite
  checks.

  The maths is the kernel's own Design module, which shipped this until the look
  left the core in 1.2, lifted unchanged: it was measured against the
  framework's colours and there was no reason to re-derive it. What did not come
  along is the size raster - the library's part sets and their knobs own that
  now.

- A **tab bar** over the controls, `Struktur | Farben`. Switching redraws the
  column and leaves the frame beside it alone: the page in it is the same page
  under either tab, so rebuilding it would cost a request and a flash for a
  click that changed which controls are on screen and nothing about the design.

- `tests/design-smoke.php` is 129 checks. The new ones: that the untouched
  palette reproduces `base.css` exactly in both modes, that both blocks publish
  the full surface vocabulary, that the three reader states are all written,
  that every solved surface clears 4.5:1 in both modes - including with a
  corporate hex nobody chose for its contrast - that the two picked colours come
  back untouched while their `-safe` roles are solved, that red stays red
  whatever the brand is, that a knob that moves moves something, and that the
  palette travels out in the list, back in on a save and through a preview
  without being written.

### Fixed before it shipped

- **A frame is a stylesheet and the markup it was drawn against, and `apply()`
  only wrote the stylesheet.** Choosing a header therefore put one variant's
  css over another variant's html - found by checking the panel's own claim
  that compiling overwrites the two frame templates, which it did not. It does
  now, under the same stamp and the same refusal as `theme.css`: a template
  somebody edited is left alone and the refusal says so.

- Nine parts, two kinds: `header` and `footer` are frames and bring a
  `template.tpl` with them; `atf`, `section`, `article`, `buttons`, `forms`,
  `lists` and `blocks` are one stylesheet each. `Setup::PARTS` is the list, in
  the order the cascade wants them concatenated in.
- `library/` ships six headers, seven footers and a `v1` per set - the frames
  that were the wizard's choices up to Nino 1.1, and a starting point per part
  that declares nothing and lists every rule `Nino.css` sets for that part,
  commented out with today's values.
- `library/base.css` is sections 1 and 2 of the `theme.css` the wizard's base
  unit delivers, byte for byte. The feature ships its own copy because a
  project deletes `_admin/install/` after setup; the test holds the two to
  each other wherever a checkout still has the installer.
- `Setup` (`data/design.php`, declared under `data` so a backup carries it):
  the set and step per part, the global step, the root size, and what was last
  compiled. Every value is normalised against the library that is really
  there - a part naming a set that is not there falls back to the first one
  that is and says so, and a set name is held to
  `[A-Za-z0-9][A-Za-z0-9._-]*` before it is joined to a path.
- The finetune knob picks rather than computes: a set declares
  `--name--less` / `--name--default` / `--name--more`, and the compiler writes
  one selection line per token into a single block at the end of the sheet.
  Global, with a per-part deviation; a part naming no step of its own follows
  the global one and keeps following it when it moves.
- The root size is `s`, `m` or `l`, compiled as the percentage pair
  `--nino-base-size` wants - relative, never a `px` length.
- `Compiler::write()` refuses `assets/theme.css` when the file on disk does
  not carry a header claiming a digest that still matches its body: the
  delivered file, or one somebody edited. `$force` is the way past it, and
  `Design::apply()` passes it through.
- `upgrade()` refreshes the setup and never recompiles on its own. A new
  version of the feature may ship changed sets; moving a site nobody asked to
  move is not an upgrade.
- `Preview` is the specimen, the frames around it and the compile, in one
  place. The panel and `design-library/preview.php` both go through it, so a
  set looks the same while it is being written as it will in a project; what
  the harness adds is the throwaway project to render against, which an
  installed site already has and a library checkout never does.
- `tests/design-smoke.php`, 106 checks.

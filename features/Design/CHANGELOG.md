# Changelog

All notable changes to the Design feature are documented in this file.
A release is the tag `design-<version>` of dapeio/nino-features.

## Unreleased

- **The words caught up with the library and the palette.** The README still
  opened with the note that every part set is the empty `v1` - the library has
  ten headers, eleven footers and five sets per part since the variants
  landed, and `v1` declares the framework's own values as triples rather than
  nothing. Its Data chapter, `feature.php`'s comment beside `data`, the
  `Compiler` and `Design\Admin` docblocks and `admin.js`'s own header all
  described the feature as it was before the Colours tab: the palette is in
  `data/design.php`, in the compiled sheet's second section and in one of the
  panel's two tabs, and `Design`'s list of the pieces named four of the five.
  `_selection()` promised `{ parts, step, size }` and returns
  `{ parts, knobs, size, colours }`. The knob example in every `v1` declared
  `--<part>-title-fontsize`, which is not one of the four knobs the panel can
  offer, so a set copied from it would publish a handle nothing turns. Two
  comments in `Setup` still named their knobs in German. And
  `design-smoke.php`'s library check accepted a step the panel cannot see: it
  matched the token with optional whitespace before the colon while
  `Setup::knobs()` matches the colon flush, in a stylesheet with its comments
  removed - so a triple written `--section-measure--less : 42rem` passed the
  suite while the knob quietly disappeared from the screen. It reads the file
  the way the panel does now, and both library checks name the file they
  failed on.

- The README no longer counts the suite's checks. The number was corrected in
  three patches running, and a reader of the README needs the suite's name,
  not its size.

- **A digest per part that was written, carried and never read.** `apply()`
  hashed the library file behind every part into `data/design.php`,
  `Setup::normalize()` carried it through every read, and `fingerprint()` took
  it straight back out again before hashing - because it decides nothing. No
  code anywhere read one. What tells a project that a set changed under it is
  `compiled`'s `input`: the whole setup plus the bytes of every library file it
  names, recorded by the last apply and compared by the panel. The three lines
  are gone, so the file holds what was chosen and nothing worked out from it,
  which is what the manual already said of it. The fingerprint is unchanged by
  this - a setup that still carries the old digest hashes to the same value as
  one without, so no project is told its stylesheet is out of date. A stored
  digest is dropped by the next write of the file, and `upgrade()` - which
  reads the setup and writes it back - is the write that does it without
  waiting for a save.

- The README's check count matches the suite again - 191 rather than the
  number it carried, which the suite passed some time ago.

- Needs Nino `^1.3`, where the constraint said `^1.2`. The sectioned `manual`
  map this manifest carries is only read by a kernel newer than the
  `v1.2.0-beta` tag - `Features::manifest()` refuses it on the tagged one -
  and `^1.2` is satisfied by that kernel, so the catalogue offered the
  feature to an installation that could not then install it. `^1.3` names
  only a kernel that can read the manifest.

### Added

- **Four more variants for every part - 36 files, and the library is a choice
  now.** `header` grows to ten and `footer` to eleven (Two decks, Pill menu,
  Quiet caps, Slim bar; Centred stack, Sitemap, Dark slab, Hairline), and every
  set part goes from one file to five. Each is one decision carried through
  rather than a sampler, so the decision is in one place when somebody changes
  it: a flat card set has no shadow anywhere, a dense form set tightens
  everything except the typing size, which stays at a rem so a phone does not
  zoom on focus.
- Several of them declare knobs `v1` never did, which is how a handle reaches a
  part that had none: `measure` for the column a wide band is read in, `volume`
  for the size of a button's or a field's own label. `Setup::KNOBS` always had
  the vocabulary; nothing in the library answered to all of it.
- `tests/design-smoke.php` holds the whole shipped library to that vocabulary
  now, rather than a fixture: a knob that is not one, a triple missing a step,
  a rule reading a token the file never declares, a variant with no name, two
  variants under one name, and a frame missing its template each fail. Every
  variant is also compiled on its own, because a setup naming all of them at
  once proves only that the last one landed.

### Changed

- **The preview specimen is written in English.** Its demonstration copy, its
  control labels and the two constants behind them were German in a catalogue
  that is English everywhere else, as were the `@knob` notes in 35 library
  stylesheets. The `@name` and `@description` a set is listed under were
  already English; those are what the panel shows.

- **The preview holds no markup in php any more.** `Preview` carried the whole
  specimen - every section, card, button and plan - as strings in three methods
  split apart only so neither would be a wall. It is
  `templates/preview-specimen.tpl` now, with the page around it in
  `templates/preview-document.tpl`, read through `\Nino\Filesystem` and filled
  with `str_replace()`; what stays in the class is which template, the
  demonstration copy and the placeholder picture. The loops are written out,
  because a specimen is a design: every variant in it is a deliberate
  demonstration rather than data, and the person who edits it is designing a
  set. See AGENTS.md, "Markup belongs in a template".
- `Preview::specimen()`, `markup()` and `document()` take `$appData` now, since
  reading a template goes through the filesystem like everything else.

- **The variant select says which version it is offering.** `v3 - Floating bar`
  rather than `Floating bar`: with five variants per part the list is something
  somebody scans, and the version is what they say and type about it. A file
  with no `@name` keeps being offered under its file name alone, rather than as
  `v3 - v3`.
- **The part column is tighter by three elements.** The sentence under each
  select and the "Finetuning" heading over the knob are gone, and the knob no
  longer holds a margin above itself - the picker, the variant and the knob are
  read together, and a paragraph between each of them was more of the screen
  than the three controls were. What the sentence said is on the control as its
  title, so hovering a variant still tells you what it is.

- **The panel's two selects no longer look like two selects.** The picker says
  which part the whole column below it is about; the variant select is that
  part's own answer, and the knob under it belongs to the same part again. Two
  identical fields a row apart said the opposite - so the picker carries the
  weight now and everything that hangs off it stands inside a rail.
- **What a select means sits between its name and the select**, not in a
  paragraph under it: a sentence floating below a control reads as the next
  thing on the screen rather than as something about the thing above it. That
  is the variant's `@description` and the root size's own line; the paragraph
  over the whole screen and the one under the Finetuning rows are gone
  altogether.
- **"The file answers to this selection" is a plain line rather than a green
  panel.** A green panel is a thing the eye keeps checking, and that one said
  nothing about the selection on screen - it spoke about the last compile and
  stayed green through every change made after it. Only a line that is
  something to act on is marked now, and the heading over it says *Compiled
  file* rather than a feature manual's *How it is used*.
- **A `Reset` at the far left of the action bar** takes the screen back to the
  stored selection. It appears the moment the two differ and goes again when
  they do not, which is the honest version of what the green panel was being
  read as. Never further back than what was saved.
- **The root size positions are a full step apart.** `s` and `l` were one
  pixel either side of `m`; at a 16px browser default the ladder is now 14/16/18
  below the breakpoint and 15/18/21 from it, which is a choice somebody can see
  on the page instead of measure. `m` is the delivered size and does not move,
  so nothing compiled before this reads differently.
- **Harmony and the second colour are one row, called Second colour.** They
  were two questions a line apart - one asking for a hex, one asking where to
  derive one - and the second answer silently beat the first, because an
  explicit Secondary overrides the whole knob. Now the four automatic positions
  and the swatch that overrides them stand in the same row: while a position is
  active the swatch shows the colour the wheel actually produced (`Colours::accent()`,
  answered by `design/list` and by every `design/preview`, so it follows a knob
  live), drawn quietly to say nobody chose it. Opening it lights no position at
  all, and `↺` hands the question back to the wheel.

### Fixed

- **An invalid byte in a value rendered as nothing.** `htmlspecialchars()`
  answers input that is not valid UTF-8 with `''` unless `ENT_SUBSTITUTE` is
  among its flags, and every call here spelled the flags out without it.
  Every call carries it now, as the kernel's do, and
  `tests/escaping-smoke.php` reads every feature for the next one.

- **The panel compiled the whole stylesheet to answer one boolean.** "Does
  the file on disk still match the screen?" was answered by building the
  sheet again and hashing the result - 43 ms of colour solving, on every list,
  on every save, and once more at the end of every apply, which therefore
  compiled twice. An apply now records what it compiled *from* - the setup's
  own choices and a hash of each library file they point at - and the answer
  is that fingerprint against the current one: 43 ms → 0.17. A setup written
  before this carries the compiled hash alone, and there the old answer is
  still the only one there is, once, until the next apply.

- **The knob table shipped three strings nothing drew.** `label`, `note` and
  `hint` went to the browser with every list, while the panel draws all three
  from text keys - and they had drifted: `harmony` read "Harmony" in the table
  while the screen said "Second colour". Gone. What a published knob really
  needs is its words in every locale, and the suite checks for those now
  rather than the docblock claiming no template needs a line.

- **A size posted as an array was a 500 on the panel's own save.** Its two
  neighbours in the same array were read with `is_array()`, `size` with a
  `(string)` cast - and that cast raises "Array to string conversion" for an
  array, a level the kernel treats as fatal. It is read like the others now
  and falls back the way any unusable size does.

- **"Nothing was overwritten" was not always true.** Applying wrote the
  stylesheet first and asked about the frame templates afterwards, so a
  project that had taken its header template over by hand - which the
  generated file invites - got a new `assets/theme.css`, kept its old header,
  and was told that neither had happened. Every file a compile would write is
  asked before the first one is written.

- **Following the picker scrolled the workbench, not only the preview.**
  `scrollIntoView()` walks every scrollable ancestor of an element, and inside a
  same-origin iframe the workbench's own pane is one of them - so the frame
  jumped to the part *and* the column beside it slid away under the selects that
  had just been used. The frame's own window is scrolled by hand now.
- The brand's contrast warning is written in place rather than redrawn with its
  column, which holds a colour picker somebody may have open.
- **The preview was not built the way a Nino page is built.** Three of its
  sections - Article, Listen & Tabellen, Bausteine - stacked four and five
  `.nino-grid-row` siblings inside one `<section>`. That row is the only thing
  carrying the horizontal padding and the max-width, and it carries no vertical
  margin at all, so the rows sat flush and the blocks under them touched. No
  page this framework produces looks like that: every page the wizard installs
  has one row per section, and `AreaComposer::render()` wraps a whole compiled
  section body - heading, content and action together - in exactly one.

  Each of the three is one row now, and the rhythm between the blocks is a
  spacing utility on the cell, which is where the Template Builder's own presets
  put it (`nino-grid-100 nino-mb-3` for a heading area, `nino-mt-3` for an
  action, `nino-mb-3` on a card). The article cards carry their grid class
  themselves rather than sitting in a wrapper `<div>`, because that is what the
  compiler writes. Cells that stack on a narrow screen - the two volume rows,
  the two halves of Forms - got the same utility, where before they met at
  whatever margin their last paragraph happened to have.

  What the preview shows did not change: 48 of the 51 rendered blocks this
  repository compares before and after are identical, and the three that differ
  are the three the specimen is in.
- `tests/design-smoke.php` now holds the specimen to that shape: one grid row
  per section, every child of a row a grid cell, and every cell but the last of
  a multi-cell row carrying a spacing utility. Proven both ways - putting one
  stacked row back fails the first, taking one `nino-mb-3` away fails the third.
- The `logo-bar` preset named `nino-mb-3` twice on its heading area, so every
  section a project inserted from it carried the class twice.

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

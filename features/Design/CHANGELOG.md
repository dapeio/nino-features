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
- `tests/design-smoke.php`, 52 checks.

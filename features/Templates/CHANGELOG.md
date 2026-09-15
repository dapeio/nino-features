# Changelog

All notable changes to the Template Builder feature are documented in this file.
A release is the tag `templates-<version>` of dapeio/nino-features.

## Unreleased

### Changed

- **Inserting a section is three steps now: choose, design, fill.** The dialog
  put everything after the library on one screen - the section's frame, its
  components and every field they bind to - and that screen is the wall of
  controls the panel was accused of being. Deciding how a section looks and
  deciding what it says are two jobs, so they are two steps: the primary
  button on the design step opens the content step instead of inserting, and
  the stepper in the header counts to three.

  The split is the Design/Data switch an existing section is edited through,
  taken apart. That is why it is the insert flow only: whoever opens a section
  that already exists usually wants one of the two and knows which, so the
  edit keeps its tabs and its single screen. A preset without named areas
  keeps the single configuration screen too - there is nothing to split - and
  the middle step is not drawn for it.

  The section's own frame belongs to the design step and is not repeated on
  the content step; the area editor is on both, showing its components on the
  first and their bindings on the second. Three new words: the step's name,
  the button that leads into it, and the way back out of it.

### Fixed

- **A component could carry a rich text field in an attribute.** A declared
  data attribute already refuses one, because the `[elements]` pass runs a
  field the model released for html through `sanitizeHtml()` - which keeps
  `"` - while an attribute needs it escaped. An image's `alt` and a button's
  `href` are attributes too and had no such rule, so binding one to a rich
  field let editor content close the attribute. Those two properties are
  marked as attributes now and refuse a rich field with the same message.

## 1.0.0 — 2026-09-10

- First release: the Template Builder, which shipped with Nino as a kernel
  module up to 1.1 and is installed from the catalogue from 1.2 on. The code is
  the same; what changed is where it lives and that a project may now be
  without it.
- Needs Nino `^1.2`. A 1.1 kernel still carries `_nino/Nino/Modules/Templates/`
  and serves that copy instead of this one - the autoloader resolves the kernel
  first, deliberately, so a shipped module can never be shadowed. The
  constraint is what refuses the install and says so.
- Its panel now sits in the workbench's **Features** group rather than under
  **Structure**: every feature's panel does, so that granting that one group is
  a bounded grant.
- `docs/` travels with it - the manual in both languages and the two recipes,
  which were `docs/templates.md` and `docs/recipes/` in the Nino repository.

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

  Editing an existing section walks the same two configuration steps. It only
  skips the library - the section already carries its preset - so its stepper
  counts to two, and the Design/Data switch beside the area tabs is gone: the
  step the dialog is on says which of the two this is, and a pair of tabs
  offering the step somebody just left is one control too many. An edit keeps
  its fine tuning, though: the frame controls the insert flow leaves out are
  still on its design step. A preset without named areas keeps its single
  configuration screen either way - there is nothing to split - and shows no
  progress bar for one step.

  The section's own frame belongs to the design step and is not repeated on
  the content step; the area editor is on both, showing its components on the
  first and their bindings on the second. Three new words: the step's name,
  the button that leads into it, and the way back out of it.

- **The preview dims every area but the one being edited.** The area tabs said
  which part of the section the controls below belong to; the frame beside
  them did not, and on a section with three areas the answer was a guess. Each
  area of a preview carries a marker now - and only a preview: a stored
  section is a file somebody reads and edits, and says nothing about a dialog
  - so the panel can hold the open one at full strength and take the rest to
  half. Switching tabs re-dims the frame from the markup it already has, with
  no request to the server; an area with no components yet dims nothing, since
  a frame dimmed end to end reads as broken rather than as empty.

- **Areas are named in the interface language.** "Title area", "Articles",
  "Intro" came out of the manifest in English wherever the panel showed them.
  A manifest names its areas twice now: `label` stays the English name the
  server composes stored strings from - an image slot caption outlives the
  interface language that made it - and the new `labelKey` is the same name as
  a fill key, which is what the panel reads. All 34 areas of the shipped
  library carry both, in English and German.

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

# Changelog

All notable changes to the Template Builder feature are documented in this file.
A release is the tag `templates-<version>` of dapeio/nino-features.

## Unreleased

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

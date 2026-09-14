# Changelog

All notable changes to the Copy to Clipboard feature are documented in this file.
A release is the tag `copy-<version>` of dapeio/nino-features.

## 1.0.0 — Unreleased

First release.

- `[copy]…[/copy]` — a copy button beside a piece of text, with `value`, `label`
  and `block`.
- What is shown and what is copied are two things: a number grouped so it can be
  read is copied without the grouping.
- Two ways of copying, because one of them is not everywhere: the clipboard API
  is secure-context only, so a site served over `http` has none. An API that
  refuses falls back to the older way before anything is said.
- What happened is said in the word on the button, not only in its colour.
- Without JavaScript the button is not shown at all and the text is left
  selectable, which is what it was before.

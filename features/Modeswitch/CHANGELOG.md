# Changelog

All notable changes to the Light/Dark Switch feature are documented in this file.
A release is the tag `modeswitch-<version>` of dapeio/nino-features.

## 1.0.0 — 2026-09-12

First release.

- `[mode-switch]` — three buttons in one group: light, follow the system, dark.
  `[mode-switch icons]` drops the words from the layout and keeps them in the
  markup, so a screen reader still reads three named buttons.
- The two forced states write `data-nino-mode` on the root element, which is what
  every Nino project's `assets/theme.css` already answers to — so the switch works
  on a project that never installed the Design feature, and a project that did
  gets its own compiled colours.
- *System* removes the attribute rather than writing a third value. There is then
  no stored state to go stale, so it keeps answering the system setting when that
  changes with the page open.
- A forced mode also sets `color-scheme`, so scrollbars and form controls follow
  the mode the reader chose rather than the one they overrode.
- The choice lives in `localStorage` and nowhere else: no cookie to declare,
  nothing sent to the server, and a cached page as switchable as a fresh one. A
  browser that refuses storage gets a switch that works for the visit rather than
  one that throws.
- Rendered `hidden` and unhidden by the script, so a reader without JavaScript
  gets no control rather than three buttons that do nothing.
- Four text fills, merged into the project's own `text/<locale>.php` at
  activation, add-only.

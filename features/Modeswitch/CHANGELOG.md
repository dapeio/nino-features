# Changelog

All notable changes to the Light/Dark Switch feature are documented in this file.
A release is the tag `modeswitch-<version>` of dapeio/nino-features.

## Unreleased

- Needs Nino `^1.3`, where the constraint said `^1.2`. The sectioned `manual`
  map this manifest carries is only read by a kernel newer than the
  `v1.2.0-beta` tag - `Features::manifest()` refuses it on the tagged one -
  and `^1.2` is satisfied by that kernel, so the catalogue offered the
  feature to an installation that could not then install it. `^1.3` names
  only a kernel that can read the manifest.

### Changed

- **The comment describing the three states stood over `TEMPLATES`.** "The
  three the switch offers, in the order it draws them" explains `MODES`; the
  constant the template patch added was put between it and `MODES`, with its
  own comment, so the file read as though the templates directory were the
  three states. Each comment stands over what it explains again.

- **The "Asset bundling" note asked a project to do something it does not
  have to.** It said `/features/Modeswitch/assets/...` resolves against the
  project root, and that a project which moved its features elsewhere with
  `NINO_FEATURES_DIR` has to name the two sources under their real path in
  `/nino/html/assets` itself. `\Nino\Filesystem` resolves the virtual
  `/features` prefix against `\Nino\Features::dir()` - a branch of its own,
  older than the `^1.3` this manifest names - so the sources are found after
  a relocation like every other file a feature addresses that way, the two
  templates this feature reads through the same prefix included.

- **The switch's markup is a template now.** The group and a button of it were
  strings `Modeswitch.php` built; they are `templates/modeswitch.tpl` and
  `modeswitch-button.tpl`, filled by token - see AGENTS.md, "Markup belongs in
  a template". The output is the same.

### Fixed

- **A switch that reached the page later stayed hidden.** The script's one
  delegated click listener was written so that a switch arriving afterwards -
  a fragment swapped in, a dialog opened - "needs nothing to wire it up", but
  a switch renders `hidden` and pressed on nothing, and the paint that unhides
  one had already happened. Such a switch stayed `display: none` with all
  three buttons reading `aria-pressed="false"`: invisible, and therefore not
  even clickable through the listener that was waiting for it. The document is
  watched for a switch being added now, and painting answers it.

- **The switch showed before the script unhid it.** It is rendered with the
  `hidden` attribute so a reader without JavaScript is not left with three
  buttons that do nothing - but the stylesheet gives `.nino-modeswitch` a
  `display` of its own, and an author rule beats the browser's own
  `[hidden] { display: none }`. So the switch was visible on every page load
  until the script ran, and stayed visible for a reader whose script never
  did. The stylesheet says it twice now, the way the other features that hide
  something do.

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

# Changelog

All notable changes to the Template Builder feature are documented in this file.
A release is the tag `templates-<version>` of dapeio/nino-features.

## Unreleased

### Changed

- **The manual and the two recipes left the kernel, and the README names the
  right permissions.** `docs/templates.md` and its German twin still described
  the panel as an optional kernel module under `_nino/Nino/Modules/Templates/`,
  in the workbench's Structure group, with its words and its preset library at
  kernel paths, and their link rows pointed at Nino's own `docs/` from a
  directory that no longer sits beside it; the two recipes named the same
  kernel paths, left `html` out of the component catalogue it has been part of
  since HTML+ became a component, and sent a reader to `tests/` files that are
  the feature's own now. The Add/Edit table promised a difference between the
  two flows that the three-step dialog ended: only the frame's height, width,
  content position, margin and padding are still left out of Add. The README
  said three actions reach into other panels and named their permissions in
  another order than the actions; there are four, and the two image-slot ones
  share one permission. `templates-smoke.php` says in one line which copy of
  the module it is measuring, because a checkout that still ships the kernel
  module serves that one and the two panel-registry checks then fail on paths
  with no word about why.

- Needs Nino `^1.3`, where the constraint said `^1.2`. The sectioned `manual`
  map this manifest carries is only read by a kernel newer than the
  `v1.2.0-beta` tag - `Features::manifest()` refuses it on the tagged one -
  and `^1.2` is satisfied by that kernel, so the catalogue offered the
  feature to an installation that could not then install it. `^1.3` names
  only a kernel that can read the manifest; every other manifest in the
  catalogue has said so since the same change was made to them.

- **Five checks that copied shipped content read its source instead, and one
  that counted source lines is gone.** `templates-smoke.php` held the list of
  presets, the three title styles, the three overlay choices, the five pricing
  Layouts and two presets' recommended scrim as literal lists, so every
  addition to the catalogue was a second edit in the suite and a red run in
  between. They read `Library::LIBRARY_ITEM`, the component catalogue, the
  frame choices and the pricing manifest now, and hold what the lists were
  standing in for: every listed preset is offered in the list's order and
  nothing else is, every style is a modifier named after it and `auto` is the
  class alone, `none` paints no scrim and every other choice paints exactly
  one, and every pricing Layout composes to markup of its own. The check that
  counted `Text::entries(` calls in `Content.php` is dropped - a count of
  source lines is a check on the shape of the code, not on what it does, and
  the reason for the one reading stands in the code beside it. The README no
  longer counts the checks of the three suites; a number that has to be
  corrected after every patch says nothing a reader needs.

### Fixed

- **Four small things: two links that went nowhere, an id read from the wrong
  attribute, a rename that threw away what was typed, and a save that stripped
  the status of its styling.** The inspector's "Upload image" and "Create in
  Admin" links addressed `/_admin/?tab=images` and `/_admin/?tab=types`; the
  workbench routes by url hash and reads no query at all, so the first click
  landed on whichever panel the rail lists first and nothing else. They name
  the screen they mean now - `#images/<group>` for an upload, `#slots` for a
  slot that has to be defined first, `#types` for an Elements type. A section's
  id was matched with `\bid\s*=`, and a word boundary also sits between the `-`
  of `data-id` and the `i` after it, so any hand-written attribute ending in
  `-id` was read as the section's own id: the panel labelled sections after
  decorative values, and saving a page whose sections carry one was refused as
  a duplicate id. The attribute is matched as an attribute now, preceded by
  whitespace. Renaming a section in the composer renamed every generated
  textfill key it owns but left the texts already typed under the old keys,
  where the next read replaced them with the preset's placeholder - the held
  values and their "this was typed in" marks move with the binding. And
  `save()` assigned `className = ''` to the save status, dropping the
  design-system class that `setDirty()`'s own comment says has to stay; it
  takes the two state classes off instead.

- **A dead `?: []` behind the preset list failed the static analysis both
  repositories run.** `LIBRARY_ITEM` is a constant and never empty, so the
  fallback could never be taken; PHPStan reports that as an error, and the CI
  of this repository and of the kernel - which analyses every feature it
  fetches - has been red since the constant arrived. The line reads the
  constant as what it is now. Nothing about the panel changed.

- **One apostrophe after one `<` in prose made a page open with nothing to
  edit.** The scanner looked for the end of a tag at every `<` in the file,
  including one somebody wrote in text, and the end of a tag is found by
  tracking quotes - so the apostrophe in "doesn't" opened one that nothing
  ever closed. The scan ran to the end of the file and stopped there: every
  section after that `<` was gone, and `split()` reported no error, because as
  far as it could tell the page had no sections in it. Whether a `<` starts a
  tag at all is asked first now, strictly - a name straight after it, the way
  html reads one - so `5 < 6 and it doesn't matter` is the text it is.

- **Editing a section and pressing Update could overwrite every one of its
  texts with the preset's placeholder.** The dialog gets its values from
  `content/fields`, which it starts when it opens and does not wait for, and
  the save posted `field.default` for every key it was holding no value for.
  So an editor who opened an existing section and pressed Update before that
  answer landed - to change a layout, or by reflex - saved the catalogue's demo
  text over what was written there. The section composed, the save succeeded,
  and nothing said anything. A key the dialog holds no value for is left out of
  the save now; a key that does not exist yet still starts as the preset's
  default, which is what a new section is for.

### Added

- **A content type "HTML+".** Insertable beside title, subtitle and button,
  and after inserting it carries an **Edit** button that opens the same large
  source editor the section's own HTML+ escape hatch opens. The difference is
  what it writes back: the escape hatch detaches the whole section from its
  preset, this writes one component and leaves the section composed around it.
  So a page can have one place whose markup is yours without the part around
  it stopping being a library section.

  Its value is template source and lives in the section's spec, not in a
  textfill - it has to, because `\Nino\Text::sanitizeValue()` turns every `[`
  and `]` into an entity, so a fill cannot carry a shortcode by construction,
  and `strip_tags()` or the inline allowlist takes the markup. Fills and
  shortcodes inside an HTML+ component survive, which is what HTML+ means.

  What it may not carry, each for its own reason: a nested `<section>`, which
  is not what the document model reads back; `script`, `iframe`, `object`,
  `embed`, `form`, `style`, because the escape hatch asks for the whole
  section and says so while this asks for a part and keeps the preset; and
  `-->`, which would close the spec's own comment marker. Every `>` in that
  marker is written as `\u003e` as well, so the source cannot end it whatever
  the list says.

### Changed

- **Renamed all part presets.** The key names the group an editor looks in -
  `hero-`, `articles-`, `image-`, `items-`, `form-`, `static-` - so the list
  reads as the thing it is. The section markers in the page units Nino's
  installer ships were renamed with them; a project that already composed a
  section carries the old key in its own `page-*.tpl` and that section is not
  recognised as a library section any more (see the note in the patch body).

- **The available presets are defined with a constant and not sorted by key.**
  The part presets in the wizard are manually sorted by casual page position.
  `image-banner` was missing from it, which took a working preset out of the
  panel without removing anything; it is back, and `templates-smoke.php` now
  holds the constant and the `library/` directory to each other, so neither
  can drift from the other unnoticed.

- **The section library is built once, not once per keystroke.** A card does
  not depend on the search text, only on whether it matches it - but the
  gallery was emptied and rebuilt on every `input` event, and every rebuilt
  card carried a fresh `<iframe>` whose `srcdoc` embeds the whole project
  stylesheet and its base64 fonts. Typing five letters over the seventeen
  shipped presets wrote 29 preview documents, 2.8 MB of them, and 230 ms of
  main thread. Measured again after: no document written at all, 1 ms. The
  cards are built once and the filter toggles a class; they are rebuilt only
  when the library itself changed or the workbench built the shell again
  around them, both read off what is there rather than announced.

  The include gallery went with it. It was a loop over `const includes = []` -
  a literal empty array, unreachable since every preset is version 3 - and the
  `tpl` category chip it fed was counted and never drawn. The library says
  what it is by having no code for the other thing.

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

- **The content editor rebuilt the whole text catalogue once per field.**
  `\Nino\Text::entry()` builds every key of `/text/global.php` and of every
  locale file, measures each one, and then walks the result for the one key
  asked for - and `content/fields` and `content/save` asked per field, up to a
  hundred of them. A page of forty fields against a catalogue of a thousand
  keys: 79.56 ms, against 2.07 for one reading and a lookup.

### Removed

- **The composer's preset-version check, and the include gallery behind it.**
  `Library::presets()` drops every manifest whose version is not 3 before the
  panel is handed one, so the script's own `isAreaPreset()` was a tautology
  over that list in all five places it was asked. And `selectInclude()` - the
  entry point of a gallery of reusable includes - was called by nothing, so
  the `_includePath` it set was `null` from the first line to the last and
  every branch that asked about it had one answer. The includes themselves
  stay: an area that takes one is offered them by `area-composer.js`.

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

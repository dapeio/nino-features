# Changelog

All notable changes to the Search feature are documented in this file.
A release is the tag `search-<version>` of dapeio/nino-features.

## 1.1.0 — 2026-09-11

### Two shortcodes, and no page

- `[search]` renders a plain **GET** form, so the query rides in the url and a
  result page can be linked, bookmarked and gone back to. Its labels come from
  its own attributes, then the project's `/search/label/submit` and
  `/search/label/placeholder` textfills, then what the feature ships in the
  interface language - a bare `[search]` already says something.
- `[search-results …]…[/search-results]` is an enclosing shortcode whose
  **body is the markup of one hit**, repeated per result, with `[[name]]` for
  anything the type's model has plus `[[.uri]]`, `[[.slug]]`, `[[.type]]`,
  `[[.locale]]`, `[[.score]]` and `[[.n]]`. That is the whole templating story:
  one search serves a product grid and a list of articles because neither is
  this feature's to describe. `type` takes one Element type or several,
  `limit` defaults to 20, `empty` says what to show when nothing was found,
  and `tag`/`class` own the wrapper.
- Still no route, no page template and no install unit. The feature ships the
  two shortcodes and a project puts them in its own page.
- A value is escaped on the way into the page unless the model marks the field
  as `html`; an array field reads as a comma-separated list; a placeholder the
  model does not have is left standing rather than emptied, the way an
  unresolved fill behaves everywhere else in Nino.

### The ranking

- **A query word that finds nothing no longer discards the document.** Every
  token used to have to reach its threshold or the document was dropped, which
  reads as reasonable until somebody types a sentence: an article titled "AI im
  Jahr 2026" was not found by "AI in 2026", and "Ausblick der Modelle" found
  nothing in a summary reading "Ein Ausblick auf Modelle und Werkzeuge". One
  filler word the text happens not to use, and the result was empty. A missed
  word now lowers the *coverage* instead, and the coverage multiplies the
  score - three words of three always outranks two of three, so a partial match
  lands below a full one rather than nowhere. At least one word still has to be
  found.

### The API

- `getHits()`: the cheap half of a search - uri, type, score, coverage and the
  priorities that carried a match, without reading a single Element. A page
  showing ten of two hundred hits has no business reading two hundred files to
  find that out.
- `getElements()` takes `$limit` and `$offset`, and both it and `getHits()`
  take **several types at once**. The scores are on one scale, so hits from two
  types interleave by score exactly as they do within one.
- Every Element `getElements()` returns now carries `.score` and `.type` beside
  the `.uri` and `.locale` it always had.
- `configuration()`: everything `/nino/elements/index` names, **with the reason
  where it cannot be used**. The silent version of this was the feature's worst
  habit - a field name with a typo was dropped without a word, a configuration
  naming two types reported "1 index created", and a wholly invalid one came
  back as "nothing is configured".
- `createIndexes()` reports that too, as `skipped` (no index written, and why)
  and `issues` (indexed, but a name in the configuration does not resolve), and
  takes one type name to rebuild just that one.
- `indexState()`: one row per Element type the project has - plus any the
  configuration names and it does not - with the fields, the issues, the
  element counts and whether the index is stale.

### The index file

- Each index carries a `.meta` block: format, build time, element count and the
  fields it was built from. A dot-prefixed key can never be a locale, so an
  index written by 1.0.0 still reads and every locale lookup walks past it.
- Stale is two stat calls: the type file written since the index was (an
  element edited while the feature was off, a restored backup, a hand edit), or
  a configuration naming other fields than the ones it was built from.

## 1.0.0 — 2026-09-07

- Moved unchanged from dapeio/nino 1.0.0-beta, where it lived under
  `app/Nino/Modules/Search/` and then `features/Search/`: the locale-aware
  fuzzy index over `/nino/elements/index`, the rebuild after every committed
  Elements write, the Search panel with its one button, and
  `tests/search-smoke.php`.
- Versioned here from now on, with its own `version` in `feature.php`,
  this changelog and the `nino` constraint `^1.0`.

# Table of Contents

A long page's own headings as a list that says which section is being read — and
an anchor on every heading, so a passage can be linked to.

```
[toc]
[toc levels="2" within="#content" title="Auf dieser Seite"]
```

## Why the browser builds it

A Nino page is assembled out of a template, sections, shortcodes and elements,
and what the headings finally are is only settled once all of that has run: a
`[posts]` list adds some, a `[template]` include brings its own, an Elements loop
makes one per entry. The finished page is the only place where the answer is
complete — and the browser is standing in it.

So `[toc]` writes a `<nav>` with its heading and an **empty** list, `hidden`, and
`toc.js` fills it from the headings that actually came out. A page whose script
never runs keeps a nav that says nothing: a table of contents with no contents is
worse than none.

## The anchors are the same answer

A link can only point at an id that is there, and a heading a project wrote by
hand usually has none. So `toc.js` gives every heading it lists an id made from
its own words — and **leaves an id the page already had exactly as it was**,
because that one may be linked to from somewhere else.

Two headings with the same words get two different ids (`kontakt`, `kontakt-2`),
and an id is checked against the whole page rather than just the list.

## Attributes

| Attribute | What it does |
| --- | --- |
| `levels=` | `2`, `3`, or both. Always in order — a list that reads h3 before h2 is not an outline of anything. Default `2,3` |
| `within=` | a CSS selector: take the headings from inside that element rather than from the whole page |
| `title=` | what stands over the list. Without it the Text panel's own words are used |

A heading with `class="nino-toc-skip"` is left out of the list and given no
anchor.

## The list is flat

One `<ol>` with the heading's level on each item and the indent in the
stylesheet, rather than an `<ol>` inside an `<ol>`. It is one ordered list of
links to one page either way, and a nested list built from a flat run of headings
has to guess what to do with an `h3` that comes before any `h2`.

The section being read is marked with `aria-current="true"` — so what the reader
sees and what a screen reader announces are the same fact, not a class only one
of the two can notice. Scrolling costs one animation frame at most, not one call
per event.

## The words

`install/text/<locale>.php` carries two fills, merged into the project's own
`text/<locale>.php` at activation, for every locale it has:

| Fill | English | Deutsch |
| --- | --- | --- |
| `[[/toc/title]]` | On this page | Auf dieser Seite |
| `[[/toc/anchor]]` | Link to this section | Link zu diesem Abschnitt |

`[[/toc/anchor]]` reaches `toc.js` as a data attribute on the nav, resolved by the
fill engine before the page was sent — a static asset cannot read a text fill.

## Asset bundling

`toc.css` and `toc.js` are added to the **site's own** bundles —
`/.cache/style.css` and `/.cache/script.js` — the same two the base install's
`html-header.tpl`/`html-footer.tpl` already load on every page.

The sources are addressed as `/features/Toc/assets/…`, which
`\Nino\Filesystem::path()` resolves against `\Nino\Features::dir()` — so they are
found wherever `NINO_FEATURES_DIR` put the features directory, and a project that
moved it has nothing to say in `/nino/html/assets` itself.

## Settings

| Setting | Default | What it does |
| --- | --- | --- |
| **Anchors on every heading** | on | put a link on every heading the list holds. Off: those headings still get their ids, because the list has to reach them, but no link is drawn |

## Data

None. The list is the page, read as it stands.

## Tests

`tests/toc-smoke.php` — the manifest, the activation and the two words it merges,
what `[toc]` writes and what it deliberately does not, the two files it puts into
the site's bundles, and deactivation. It runs `tests/toc-js-smoke.js` too where
`node` is on the path.

`tests/toc-js-smoke.js` — what `toc.js` does over a DOM stand-in: which headings
it takes and which it leaves, the ids it makes and the one it must not touch, the
anchors, which item it marks while the page is scrolled, and that a page with no
headings keeps a nav that says nothing.

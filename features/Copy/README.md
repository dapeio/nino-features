# Copy to Clipboard

A copy button on the one thing that would otherwise be typed out by hand.

```
[copy]DE02 1203 0000 0000 2020 51[/copy]
[copy label="IBAN" value="DE02120300000000202051"]DE02 1203 0000 0000 2020 51[/copy]
[copy block]php bin/check.sh[/copy]
```

## Why a shortcode and not an attribute

Two reasons, and the second settled it. What is copied is **content**, and
content belongs between two tags rather than inside an attribute where a quote
would end it. And the button's three words are **text fills**, which only
something rendered on the server can resolve — a static asset cannot read one
(`docs/development.md`, "Assets Are Not Templates"), so a button this feature did
not render would be a button with no word in the project's language.

## What is shown and what is copied

Two things, and `value=` is where they part:

```
[copy value="DE02120300000000202051"]DE02 1203 0000 0000 2020 51[/copy]
```

grouped so it can be read, copied without the grouping. Where `value=` is not
given, what is copied is exactly what stands there.

## Attributes

| Attribute | What it does |
| --- | --- |
| `value=` | copy this instead of what is shown |
| `label=` | what the button copies, for a reader who cannot see what it stands beside. The accessible name becomes `Kopieren: IBAN` — "Copy" is what it does, "IBAN" is what it copies, and one without the other is half a name |
| `block` | a `<pre>` rather than a `<span>`, with the whitespace kept and allowed to scroll |

## Two ways of copying

The clipboard API where the browser has it **and is allowed to use it** — it is
secure-context only, so a site served over `http` has none at all — and a
selection plus `execCommand` where it is not. An API that refuses falls back to
the older way before anything is said. Where neither works the button says
`Strg+C drücken` and the text is left selected, which is the thing the reader was
going to do anyway.

What happened is said in the **word on the button**, not only in its colour: a
colour says it to whoever can see it and to nobody else. After a moment it is a
copy button again.

## Without JavaScript

The button is written `hidden` and unhidden by `copy.js`. What is left is the
text, selectable, which is what it was before. A button that copies nothing is
worse than no button.

## The words

| Fill | English | Deutsch |
| --- | --- | --- |
| `[[/copy/do]]` | Copy | Kopieren |
| `[[/copy/done]]` | Copied | Kopiert |
| `[[/copy/failed]]` | Press Ctrl+C | Strg+C drücken |

Merged into the project's own `text/<locale>.php` at activation, for every locale
it has, and handed to `copy.js` on the button itself.

## Asset bundling

`copy.css` and `copy.js` are added to the **site's own** bundles —
`/.cache/style.css` and `/.cache/script.js` — the same two the base install's
`html-header.tpl`/`html-footer.tpl` already load on every page.

The sources are addressed as `/features/Copy/assets/…`, which
`\Nino\Filesystem::path()` resolves against the project root. A project that moved
its features elsewhere with `NINO_FEATURES_DIR` has to say so in
`/nino/html/assets` itself.

## Settings

None. What is copyable is decided where it is written, one element at a time,
which is the only place that knows.

## Data

None.

## Tests

`tests/copy-smoke.php` — the manifest, the activation and the three words it
merges, the shortcode over its body, its value and its label, the two files it
puts into the site's bundles, and deactivation. It runs `tests/copy-js-smoke.js`
too where `node` is on the path.

`tests/copy-js-smoke.js` — what `copy.js` does over a DOM stand-in: what is put
into the clipboard and what is not, that the older way is tried when the API is
missing or refuses, and that the word on the button says what happened and goes
back afterwards.

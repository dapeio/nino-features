# Elements search

**Key:** `search` · **Class:** `\Nino\Modules\Search` · **Version:** 1.1.0 · **Nino:** `^1.0`

A small weighted fuzzy index over the fields of configured Element types,
grouped by locale. Two shortcodes put a search form and its results on any
page; project code searches through `\Nino\Modules\Search::getElements()`
and receives complete canonical Elements in score order. The index is a derived file per type, rebuilt after
every committed Elements write of that type and, all at once, from the
workbench's **Search** panel. Which types and fields are indexed is
`config.php` work, under `/nino/elements/index`.

One directory, the shape the [feature recipe](https://github.com/dapeio/nino/blob/main/docs/recipes/feature.md)
describes: `feature.php`, `Search.php`, `Shortcodes/Shortcodes.php`,
`Admin/Admin.php`, `assets/`, `text/`, `tests/`. There is no install unit -
the feature ships no page and no template, only the two shortcodes a project
puts in its own - and no `data` entry in the manifest, because the index
files are derived and rebuilt on demand. The changes per version are in
[CHANGELOG.md](CHANGELOG.md).

## The two shortcodes

The feature registers no route and brings no page. What it brings is a form
and a way to draw its answer; where they go, and what a hit looks like, is the
project's:

```
[search submit="[[/page-products/search/submit]]" placeholder="[[/page-products/search/placeholder]]"]

[search-results key="q" type="/products"]<h5>[[title]]</h5> <p>[[description]]</p>[/search-results]
```

`[search]` is a plain **GET** form, so the query rides in the url and a result
page can be linked, bookmarked and gone back to. `[search-results]` is an
enclosing shortcode, and **its body is the markup of one hit** - repeated once
per result, with `[[name]]` for anything the type's model has. That is the
whole templating story: one search can serve a product grid and a list of
articles because neither of them is this feature's to describe.

Both read the same query variable and have to agree on its name - `key` on
both, default `q`.

### `[search]`

| | |
| --- | --- |
| `key` | the query variable, default `q` |
| `action` | where the form submits, default the page it is on |
| `placeholder` | the field's placeholder, and its accessible name |
| `submit` | the button |
| `label` | the accessible name, where it should differ from the placeholder |
| `class` | replaces the form's classes entirely (default `nino-form nino-form--inline nino-search`) |

`submit` and `placeholder` fall back to the project's own textfills
`/search/label/submit` and `/search/label/placeholder`, and then to what the
feature ships in the interface language - so a bare `[search]` already says
something. A fill an attribute names but the project never defined arrives here
as the brackets themselves; those are treated as absent rather than printed on
a button in front of a visitor.

### `[search-results]`

| | |
| --- | --- |
| `type` | one Element type, or several separated by commas. Both `products` and `/products` |
| `key` | the query variable, default `q` - must match the form's |
| `limit` | how many hits to draw, default 20, at most 200 |
| `empty` | what to say when the query found nothing. Without it, nothing is drawn |
| `tag` / `class` | the wrapper, default `<div class="nino-search-results">`. `tag="none"` leaves the rows unwrapped |

Beside the model's own fields, every row can use:

| | |
| --- | --- |
| `[[.uri]]` | the Element uri, eg. `/products/lampe` |
| `[[.slug]]` | its last segment, `lampe` - what a project's own route builds a link out of, since an Element uri is not a public path |
| `[[.type]]` · `[[.locale]]` | the type it came from, the locale it was read in |
| `[[.score]]` | its score, rounded to three places |
| `[[.n]]` | its 1-based place in the list |

Values are escaped on the way into the page, unless the model marks the field
as `html`. An array field reads as a comma-separated list. A placeholder the
model does not have is **left standing** rather than emptied - that is what an
unresolved fill does everywhere else in Nino, and a typo nobody can see is a
typo nobody fixes.

Nothing searched for renders nothing at all, which is a different thing from
having searched and found nothing. A page carrying a query is never
page-cached (`\Nino\Modules\Cache` refuses anything with query variables), so
a result page is always the answer to what was actually asked.

## API

Beside the shortcodes, the feature's public surface:

| | |
| --- | --- |
| `getHits( &$appData, string\|array $type, string $query, int $limit = 0, int $offset = 0 ): array` | the cheap half: `{ uri, type, score, coverage, matched, fields }` per hit, best first, without reading a single Element. A page that shows ten of two hundred hits has no business reading two hundred files to find that out |
| `getElements( &$appData, string\|array $type, string $query, int $limit = 0, int $offset = 0 ): array` | the same search with the Elements read - canonical Elements, best score first, each carrying `.score` and `.type` beside its `.uri` and `.locale` |
| `configuration( &$appData ): array` | everything `/nino/elements/index` names, as `/type => { fields, issues }` - **including what cannot be used, and why** |
| `indexState( &$appData ): array` | one row per Element type the project has, plus any the configuration names and it does not: title, model, element count, configured fields, issues, and whether the index is missing or stale. What the panel draws |
| `createIndexes( &$appData, string $only = '' ): array` | recreate every valid configured index, or just the one type named; answers `{ created, elements, failed, skipped, issues }` |
| `callbackElementsCommitted()` | registered in `init()` under `'/nino/elements/committed'`: after an insert, update or delete of a configured type has committed, that one type's index is recreated. A failed write is reported with `trigger_error()` and never rolls back the Element commit it follows |
| `Shortcodes::query( string $key ): string` | what the visitor typed, for one query variable name |

`skipped` names a configured type that produced no index and why (`the model of "/products" has no field "titel"`); `issues` names a type that *is* indexed but whose configuration holds a name that does not resolve. Both used to be dropped silently - a configuration naming two types reported "1 index created", and a wholly invalid one came back as "no search indexes are configured".

`init()` registers the callback and the two shortcodes, and nothing else: activation creates no file.

## The panel

`\Nino\Modules\Search\Admin` is answered by `adminPanels()`, so it is in the
workbench exactly while the feature is active - after activating or
deactivating, reload the page.

| | |
| --- | --- |
| Navigation | **Search**. A panel a feature brings always lands in the workbench's **Features** group, whatever its own `nav()` names |
| Permission | `/_admin/search/manage` on every one of its actions. No session is a `401`, a session without the permission a `403` |
| Panes | `search-list`, `search-type`, `search-probe`, drawn by `assets/admin.js` and styled by `assets/admin.css` |
| Dashboard | a tile counting the indexed Elements, which says so when any index is stale. Absent while nothing is configured |
| Activity log | `Rebuild Search Index` and `Configure Search Index (/type)` |
| Text | `text/en_US.php` and `text/de_DE.php` - the panel's own words, merged into the workbench's fills while the feature is active |

### Index - the list

One row per Element type **the project has**, not only per configured one -
the difference is the point, because a type nobody indexed is exactly what
somebody is looking for when they wonder why a search finds nothing:

| Type | Indexed fields | Elements | State |
| --- | --- | --- | --- |
| products `/products` | `title` `description` `keywords` | 3 | current |
| articles `/articles` | — | 2 | not indexed |

The fields are chips in priority order, so the weighting is readable without
opening anything. **A configured name that does not resolve is printed in the
row with its reason** - `the model of "/products" has no field "beschreibung"` -
rather than dropped in silence the way 1.0.0 dropped it. The state is one of
*current*, *stale*, *not built*, *not indexed* or *broken*, and a row that is
stale or unbuilt carries its own rebuild beside the **Rebuild all** in the
action bar.

### Type - the editor

Four slots, strongest to weakest, each a `<select>` **over that type's own
model** - and only over the fields that carry text, so `image`, `element` and
`boolean` are not on the list at all. A field name cannot be mistyped into a
slot this way, which is the single most common way the configuration used to
end up quietly doing nothing.

The weight sits beside each slot, read from `Search::WEIGHTS` rather than
written into the interface, so the panel cannot promise a number the ranking
does not use.

**Save and build** writes `/nino/elements/index` into `config.php` and then
builds that one index, and says which of the two just happened. Taking every
field out takes the type out of the configuration and **removes its derived
file with it**: an index nobody searches is a copy of the content with nothing
reading it.

That key had no editor at all before, which is the one case where a panel
owning a `config.php` key is uncontroversial - there is no second writer to
disagree with (see the Config panel's own docblock on why keys with editors
leave it).

### Probe - try it

A query, the types to ask, the locale, and the hits as the page would get
them: place, label, score, coverage and which of the chosen fields carried the
match.

This is the screen the panel exists for. Search is a *ranking* feature and a
ranking is invisible: move a field from priority 1 to 0 and the order changes
here, in the same panel, without a page to test it on. It runs the real
`getHits()` against the index that is on disk right now, so it is the same
answer a visitor would get, not a simulation of one.

The reasons a configured name did not resolve are English, like every other
`\Nino\Http::fail()` message the workbench surfaces; the panel's own words are
translated.

## Configuration

Switch the feature on in the workbench's Features panel - or, by hand, list
its class in `/nino/modules` - and then use the **Search** panel, which writes
the configuration for you. By hand it is one `config.php` key:

```php
return [
	'/nino/modules' => [
		// other modules ...
		'\\Nino\\Modules\\Search',
	],

	'/nino/elements/index' => [
		'articles' => [
			0 => 'title',
			1 => 'summary',
			2 => 'keywords',
			3 => 'author',
		],
	],
];
```

The outer key is one flat Element type, with or without its leading slash
(`articles` and `/articles` are the same). The inner keys are the four
ranking priorities: `0` is strongest, `3` is weakest, and each priority
names one field from that type's current model. Invalid types, priorities
and field names are ignored - a type whose file does not exist under
`elements/`, a priority outside `0..3`, a field the model does not have. A
type that keeps no valid field is not indexed at all.

`/nino/elements/index` is a plain `config.php` key, not a feature setting:
the manifest declares no settings (`'settings' => []`), and the Features panel
shows no form for this feature. The **Search** panel is its editor, and the
only one - a second, unvalidated way to write the same data is a way to
corrupt it.

Activation registers the post-commit Elements callback and the two shortcodes,
but creates no file on its own. Use **Rebuild all** in the **Search** panel for
the initial build. Every press recreates every valid configured index.
Afterwards, every successful insert, update or delete of a configured type
recreates that one type after the Element file has committed. A type
`articles` is stored as the single derived file `/data/index-articles.php`
(normally `private/data/index-articles.php`), one plain PHP array grouped by
locale, then by Element uri, then by priority - behind a `.meta` entry holding
the format, the build time, the element count and the fields it was built
from. A dot-prefixed key can never be a locale, so an index written by 1.0.0
still reads and every locale lookup walks straight past it.

`indexState()` calls an index **stale** on two stat calls: the type file was
written since the index was (an element edited while the feature was off, a
restored backup, a hand edit), or the configuration names other fields than
the ones `.meta` records.

The index deliberately has no signature, revision or sidecar lock and is
rewritten directly and non-atomically as a complete PHP array. Reads are
strictly read-only: a missing or malformed file returns no hits and is not
repaired. Press the panel's button to recreate all indexes after
configuration changes, manual Element-file edits, or an interrupted index
write. The generated files contain normalized search text, are not source
content, and do not belong in an installer package or a backup's manifest -
which is why the feature's `data` list is empty.

Project code searches the current locale and receives the complete
canonical Elements in score order:

```php
$hits = \Nino\Modules\Search::getElements(
	$appData,
	[ 'articles', 'projects' ],          // one type or several
	(string) ( $_GET['q'] ?? '' ),
	20                                    // and say a number
);
```

The search is case-insensitive, strips markup, decodes entities, flattens
text and number values from arrays, and rewrites `ä`, `ö`, `ü` and `ß` as
`ae`, `oe`, `ue` and `ss` - so typing "Strasse" also finds "Straße". Exact,
prefix and substring matches are preferred; other words use a
length-dependent Unicode-bigram similarity. Field priorities affect ranking,
not whether a word counts as a hit: a match in priority `0` weighs `1.00`,
in `1` `0.70`, in `2` `0.45`, in `3` `0.25`. An exact phrase found in a
field receives an additional bonus, and equal scores are ordered by Element
uri. Work is bounded to 256 query characters and the first 12 unique tokens.
Empty queries, unknown or unconfigured types, missing locale data and
unreadable indexes return `[]`.

### Coverage, and why not every word has to match

Up to 1.0.0 every query word had to reach its threshold or the document was
discarded. That reads as reasonable until somebody types a sentence: an
article titled *"AI im Jahr 2026"* was not found by **"AI in 2026"**, and
**"Ausblick der Modelle"** found nothing in a summary reading *"Ein Ausblick
auf Modelle und Werkzeuge"*. One filler word the text happens not to use, and
the result is empty. Visitors type sentences.

A word that finds nothing now lowers the **coverage** - the share of the
query that was found - and the coverage multiplies the score. Three words of
three always outranks two of three, so a partial match lands below a full one
rather than nowhere. At least one word still has to be found, or the document
is not a hit at all.

The other half of that bargain: a filler word now matches whatever happens to
carry it, so a query of several words returns more rows than it used to, with
the weak ones at the bottom. Give `$limit` a number on a result page.

## Data

No file the project has to keep: `/data/index-<type>.php` per configured
type, derived from the Element files and recreated by the panel's button or
the next committed write. The manifest's `data` list is empty on purpose,
and the feature registers no restore callback - after a restore, press the
button.

## Tests

`tests/search-smoke.php` is the feature's own test. It loads Nino's
`tests/harness.php` from the checkout three levels up - where the feature
sits in a project - or from the one `NINO_ROOT` names, and defines
`NINO_FEATURES_DIR` as this feature's parent directory, so the kernel
serves the class from wherever the feature is:

```bash
php features/Search/tests/search-smoke.php
NINO_ROOT=../nino php features/Search/tests/search-smoke.php
```

It covers the panel's four actions - the rows the list draws, the editor
writing `config.php` and refusing a field the model does not have, an empty
field map taking the type out with its derived file, the probe's scores and
its locale, the dashboard tile, and both refusals (`401` without a session,
`403` without the permission) - the two shortcodes rendered the way a template
renders them -
through `\Nino\Html::renderHtml()`, so the fills in the attributes resolve
before the shortcode sees them and the `[[field]]` placeholders in the body
survive to it - the configuration boundary (both slash forms, invalid priorities,
fields and types named with their reasons), that activation registers the
callback and creates no file, the first committed write creating the index,
the shape and normalization of the derived file and its `.meta` block, the
explicit rebuild reporting what it skipped and why, a rebuild of one type
alone, unconfigured types staying unindexed, the fuzzy ranking (priority
order, spelling errors, umlauts, html and entities, array fields), coverage
scoring on the three sentences that used to come back empty, `limit` and
`offset`, several types as one ranked list, the rows `indexState()` draws
including both ways of being stale, locale selection, refresh after update
and delete, read-only handling of a deleted or malformed index, the panel
action refusing an unauthenticated request and rebuilding every configured
index for an authenticated one, an empty configuration as a successful
no-op, an index write failure that cannot roll back the Element commit, and
the `500` the action answers when a file cannot be written. `bin/check.sh`
runs it against the checkout beside this repository.

# Elements search

**Key:** `search` · **Class:** `\Nino\Modules\Search` · **Version:** 1.0.0 · **Nino:** `^1.0`

A small weighted fuzzy index over the fields of configured Element types,
grouped by locale. Project code searches through
`\Nino\Modules\Search::getElements()` and receives complete canonical
Elements in score order. The index is a derived file per type, rebuilt after
every committed Elements write of that type and, all at once, from the
workbench's **Search** panel. Which types and fields are indexed is
`config.php` work, under `/nino/elements/index`.

One directory, the shape the [feature recipe](https://github.com/dapeio/nino/blob/main/docs/recipes/feature.md)
describes: `feature.php`, `Search.php`, `Admin/Admin.php`, `assets/`,
`text/`, `tests/`. There is no install unit - the feature ships nothing for
the website - and no `data` entry in the manifest, because the index files
are derived and rebuilt on demand. The changes per version are in
[CHANGELOG.md](CHANGELOG.md).

## Routes and API

The feature registers no route. Its public surface is one method and one
callback:

| | |
| --- | --- |
| `getElements( array &$appData, string $elementType, string $searchString ): array` | search the current locale's index of one type; the canonical Elements, best score first |
| `createIndexes( array &$appData ): array` | recreate every valid configured index; answers `{ created, elements, failed }` - the number of index files written, the number of distinct Elements in them, and the type uris whose file could not be written. What the panel's button calls |
| `callbackElementsCommitted()` | registered in `init()` under `'/nino/elements/committed'`: after an insert, update or delete of a configured type has committed, that one type's index is recreated. A failed write is reported with `trigger_error()` and never rolls back the Element commit it follows |

`init()` does nothing but register the callback: activation creates no file.

## The panel

`\Nino\Modules\Search\Admin` is answered by `adminPanels()`, so it is in the
workbench exactly while the feature is active - after activating or
deactivating, reload the page.

| | |
| --- | --- |
| Navigation | **Search** in the System group (uri `search`, position 30) |
| Permission | `/_admin/search/manage` on its one action - a developer's permission |
| Action | `search/createindex` (`apiCreateIndex()`): calls `createIndexes()`; answers `200` with `{ created, elements, failed: [] }`, or `500` naming the types whose index could not be written |
| Pane | `search-form`, rendered by `assets/admin.js`: a heading, the hint, one button **Create searchindex**, and the outcome - "Created 2 search indexes for 41 elements.", or "No search indexes are configured." |
| Activity log | `log()` writes `Rebuild Search Index` for every press |
| Text | `text/en_US.php` and `text/de_DE.php` - the panel's own words, merged into the workbench's fills while the feature is active |

Every press recreates every valid configured index, whichever one is stale.

## Configuration

Switch the feature on in the workbench's Features panel - or, by hand, list
its class in `/nino/modules` - and configure the index in `config.php`:

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
the manifest declares no settings (`'settings' => []`), and the Features
panel shows no form for this feature.

Activation registers the post-commit Elements callback but creates no file
on its own. Use **Create searchindex** in the **Search** panel for the
initial build. Every press recreates every valid configured index.
Afterwards, every successful insert, update or delete of a configured type
recreates that one type after the Element file has committed. A type
`articles` is stored as the single derived file `/data/index-articles.php`
(normally `private/data/index-articles.php`), one plain PHP array grouped by
locale, then by Element uri, then by priority.

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
	'articles',
	(string) ( $_GET['q'] ?? '' )
);
```

The search is case-insensitive, strips markup, decodes entities, flattens
text and number values from arrays, and rewrites `ä`, `ö`, `ü` and `ß` as
`ae`, `oe`, `ue` and `ss` - so typing "Strasse" also finds "Straße". Each
query word must match. Exact, prefix and substring matches are preferred;
other words use a length-dependent Unicode-bigram similarity. Field
priorities affect ranking, not whether a word counts as a hit: a match in
priority `0` weighs `1.00`, in `1` `0.70`, in `2` `0.45`, in `3` `0.25`. An
exact phrase found in a field receives an additional bonus, and equal
scores are ordered by Element uri. Work is bounded to 256 query characters
and the first 12 unique tokens. Empty queries, unknown or unconfigured
types, missing locale data and unreadable indexes return `[]`.

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

It covers the configuration boundary (both slash forms, invalid priorities,
fields and types ignored), that activation registers the callback and
creates no file, the first committed write creating the index, the shape
and normalization of the derived file, the explicit rebuild reporting only
valid types, unconfigured types staying unindexed, the fuzzy ranking
(priority order, spelling errors, umlauts, html and entities, array fields,
every token required), locale selection, refresh after update and delete,
read-only handling of a deleted or malformed index, the panel action
refusing an unauthenticated request and rebuilding every configured index
for an authenticated one, an empty configuration as a successful no-op, an
index write failure that cannot roll back the Element commit, and the `500`
the action answers when a file cannot be written. `bin/check.sh` runs it
against the checkout beside this repository.

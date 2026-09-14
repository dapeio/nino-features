# Countdown

The time left until a moment, counted down on the page.

```
[countdown to="2026-12-24 18:00"]
[countdown to="2026-12-24 18:00" units="days,hours" format="d.m.Y H:i" done="Es ist so weit"]
```

## What the server writes, and what the browser does

The server writes the **moment**, once, as an ISO-8601 string with the site's own
offset in it — `2026-12-24T18:00:00+01:00`. A reader in another timezone
therefore counts down to the same *instant* rather than to the same wall clock.

The arithmetic is the browser's. A page cached for an hour would otherwise be an
hour wrong, which is the one thing a countdown must not be.

## Without JavaScript

What stands there is **the date**, in a `<time datetime="…">` — written out for a
reader and machine-readable for everything else:

```html
<time class="nino-countdown-date" datetime="2026-12-24T18:00:00+01:00">2026-12-24 18:00</time>
```

That is the markup. `countdown.js` puts the counter in front of it and takes it
out again when the moment passes. A countdown that cannot count is still a date,
which is the fact it was carrying all along.

## Attributes

| Attribute | What it does |
| --- | --- |
| `to=` | the moment, in anything PHP's `DateTimeImmutable` reads, in the site's timezone. A date this cannot read renders nothing and says so in the log |
| `units=` | which parts are shown: any of `days`, `hours`, `minutes`, `seconds`. Always drawn largest first whatever order they are written in — "3 Minuten 2 Tage" is not a duration anybody reads. Default: all four |
| `format=` | how the date under the counter is written, in PHP's `date()` letters. Default `Y-m-d H:i`, which is unambiguous in every language |
| `done=` | what stands there once the moment has passed. Without it the Text panel's own sentence is used; given `done=""`, the date itself comes back |

A countdown written with fewer parts carries its whole remainder in them: in days
alone, four and a half days is **4**, not 5.

## The words

Every unit is named in **both** its forms — `1 Tag`, `2 Tage` — because "1 days"
is what a counter says otherwise. Both reach the browser as data attributes on
the part they belong to, resolved by the fill engine before the page was sent: a
static asset cannot read a text fill, so `countdown.js` is handed the two words
and only chooses between them.

| Fill | English | Deutsch |
| --- | --- | --- |
| `[[/countdown/day]]` / `[[/countdown/days]]` | day / days | Tag / Tage |
| `[[/countdown/hour]]` / `[[/countdown/hours]]` | hour / hours | Stunde / Stunden |
| `[[/countdown/minute]]` / `[[/countdown/minutes]]` | minute / minutes | Minute / Minuten |
| `[[/countdown/second]]` / `[[/countdown/seconds]]` | second / seconds | Sekunde / Sekunden |
| `[[/countdown/done]]` | The time has come | Es ist so weit |

Merged into the project's own `text/<locale>.php` at activation, for every locale
it has. From then on they are the project's.

## Asset bundling

`countdown.css` and `countdown.js` are added to the **site's own** bundles —
`/.cache/style.css` and `/.cache/script.js` — the same two the base install's
`html-header.tpl`/`html-footer.tpl` already load on every page.

The sources are addressed as `/features/Countdown/assets/…`, which
`\Nino\Filesystem::path()` resolves against the project root. A project that
moved its features elsewhere with `NINO_FEATURES_DIR` has to say so in
`/nino/html/assets` itself.

One timer serves every countdown on a page — they all read the same clock — and
it stops as soon as the last one has run out.

## Settings

None. Which moment, which parts of it and what stands there afterwards all belong
to the one place the countdown is written. A sale ending and a conference opening
are not the same countdown, and a site may well have both.

## Data

None. The moment is written into the page it counts down on, and what is left of
it is arithmetic in the reader's own browser.

## Tests

`tests/countdown-smoke.php` — the manifest, the activation and the nine words it
merges, the shortcode over its units and formats and every way of getting the
moment wrong, the two files it puts into the site's bundles, and deactivation. It
runs `tests/countdown-js-smoke.js` too where `node` is on the path.

`tests/countdown-js-smoke.js` — what `countdown.js` does over a DOM stand-in at a
clock the test sets: how a remainder is split across the parts, which of the two
forms of a name is used, what happens at the moment itself, and that a countdown
whose moment cannot be read is left alone.

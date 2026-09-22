# Countdown

The time left until a moment, counted down on the page.

```
[countdown to="2026-12-24 18:00" tz="Europe/Berlin"]
[countdown to="2026-12-24 18:00" units="days,hours" format="d.m.Y H:i" done="Es ist so weit"]
```

## What the server writes, and what the browser does

The server writes the **moment**, once, as an ISO-8601 string with its offset in
it — `2026-12-24T18:00:00+01:00`. A reader in another timezone therefore counts
down to the same *instant* rather than to the same wall clock.

The arithmetic is the browser's. A page cached for an hour would otherwise be an
hour wrong, which is the one thing a countdown must not be.

## Which offset

`18:00` is a different instant in Berlin than it is in London, so a wall-clock
time is only half a moment. `tz=` is the other half, and it is written where the
moment is written, because that is where it belongs: a sale ending in Berlin and
a conference opening in Lisbon are not the same countdown, and a site may well
have both.

Nino has no timezone of its own — there is no such key in
`\Nino\AppData::DEFAULTS` and the kernel calls no `date_default_timezone_set()`.
So a `to=` written **without** `tz=` is read in whatever timezone the php process
runs in, which is `UTC` unless the host says otherwise. A `to=` that carries its
own offset or zone name (`2026-12-24T18:00:00+01:00`, `2026-12-24 18:00
Europe/Berlin`) is read at that one, and `tz=` does not enter into it.

A `tz=` naming a timezone PHP does not know renders nothing and says so in the
log, the same way an unreadable date does: a counter an hour off looks right,
which is worse than one that is not there.

## Without JavaScript

What stands there is **the date**, in a `<time datetime="…">` — written out for a
reader and machine-readable for everything else:

```html
<time class="nino-countdown-date" datetime="2026-12-24T18:00:00+01:00">2026-12-24 18:00</time>
```

That is the markup. `countdown.js` puts the counter in front of it and takes it
out again when the moment passes. A countdown that cannot count is still a date,
which is the fact it was carrying all along.

Where `done=` puts a sentence in that element's place, the `datetime` goes with
the date it belonged to: the machine-readable half of a `<time>` is the half of
what the element says, and "The time has come" is not that instant.

## Attributes

| Attribute | What it does |
| --- | --- |
| `to=` | the moment, in anything PHP's `DateTimeImmutable` reads. A date this cannot read renders nothing and says so in the log |
| `tz=` | the timezone a wall-clock `to=` is read in, as an identifier PHP knows — `Europe/Berlin`. Without it the timezone the server runs in is used; see *Which offset* above |
| `units=` | which parts are shown: any of `days`, `hours`, `minutes`, `seconds`. Always drawn largest first whatever order they are written in — "3 Minuten 2 Tage" is not a duration anybody reads. Default: all four |
| `format=` | how the date under the counter is written, in PHP's `date()` letters. Default `Y-m-d H:i`, which is unambiguous in every language |
| `done=` | what stands there once the moment has passed. Without it — and with `done=""`, which is the same thing — the Text panel's own sentence is used; empty that fill in the Text panel and the date itself comes back |

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
`\Nino\Filesystem::path()` resolves against `\Nino\Features::dir()` — so they
are found wherever `NINO_FEATURES_DIR` put the features directory, and a
project that moved it has nothing to say in `/nino/html/assets` itself.

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
merges, the shortcode over its units and formats, the timezone a wall-clock
moment is read in, and every way of getting the moment wrong, the two files it
puts into the site's bundles, and deactivation. It runs
`tests/countdown-js-smoke.js` too where `node` is on the path.

`tests/countdown-js-smoke.js` — what `countdown.js` does over a DOM stand-in at a
clock the test sets: how a remainder is split across the parts, which of the two
forms of a name is used, what happens at the moment itself and what the `<time>`
carries afterwards, and that a countdown whose moment cannot be read is left
alone.

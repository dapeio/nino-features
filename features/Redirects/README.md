# Redirects

Old addresses that still work.

Every site outlives some of its own addresses. A page is renamed, a section is
reorganised, a site moves onto Nino from something that spelled its urls
differently - and every link anybody ever made to the old address, every
bookmark and every search result, lands on a 404 that says nothing.

This feature is two halves. The first is a list of rules: an old path, where it
sends, and whether it moved for good. The second is the list of addresses
nothing answered, which is where the rules worth writing come from - a redirect
nobody knows is missing does not get written.

- **Key** `redirects` · **Class** `\Nino\Modules\Redirects` · **Needs** Nino
  `^1.3`
- Keeps `data/redirects.php`, declared under `data` so a backup carries it
- Brings no template, no shortcode and no route

## A rule

| | |
| --- | --- |
| **Old address** | A path of this site: `/old/page`. A query and a fragment are cut off - a rule is about a path, and one that only applied to a single spelling of the same address would look broken. |
| **Sends to** | A path of this site, or an `https://` address of another one. `http` is refused: a redirect is the one moment a site chooses the next address for somebody, and choosing a plaintext one hands that request to whoever is on the wire. |
| **Kind** | `301` moved for good - what a search engine acts on and a browser caches. `302` moved for now. Nothing else: `307`/`308` are for a request with a body, and this only ever answers `GET` and `HEAD`. |
| **Everything below it too** | A subtree. What stood after the old prefix stands after the new one, so a section that moved takes its pages with it rather than sending every one of them to the same page. |

An exact rule wins over a subtree rule, however long either is: *this page moved
there* is a statement about that page, and a subtree rule over it is a statement
about everything else. Among subtree rules the longest prefix wins, so a rule
for `/shop/archive` is reached before the one for `/shop` that would swallow it.

## Only where nothing else answers

A rule is never consulted for a path that has a route. That is the one decision
that makes this safe to switch on: a mistyped rule cannot take a working page
off the site, and a rule for an address that comes back cannot shadow it.

It is asked of the router (`\Nino\Http::requestRoute()`) rather than read off
the status code, because a project's own `/404` route can answer with any status
it likes and a soft 404 would otherwise look like a page.

Two consequences worth knowing:

- **A wildcard route answers everything below itself.** The Posts feature
  registers one `GET://blog/*`, so `/blog/anything` has a route whether or not
  a post is there - and a moved post is that feature's to redirect, not this
  one's.
- **`POST` is never redirected.** A `POST` is a request with a body and an
  intention. `301` and `302` let a browser drop both and repeat it as a `GET`,
  and `307`/`308` would ask it to post the same body to an address the sender
  never chose.

## The addresses nothing answered

While **Remember what happened** is on - it is, by default - a request that
found neither a route nor a rule is written down. The path only. Not who asked,
not what they came from: a list of addresses to fix is not a visitor log, and
this file is in every backup.

Not every path, either. Only what a page's address looks like: nothing below
`/_` or `/.` - the workbench and a module's own technical endpoints, the same
two prefixes the SEO feature leaves out of a sitemap - and no dot in the last
segment, or a name ending in `.html`/`.htm`, which is what a site migrated from
somewhere else still gets asked for. Everything else that reaches a 404 on
a public site is a scanner looking for `wp-login.php` and `.env`, and a list of
those is a list nobody reads twice.

At most 50 are kept, the most asked for first. What makes the list useful is
that the ones worth a rule are near the top, not that it is complete. An
address that has just been asked for keeps its place on a list that is already
full, taking it from the least asked for - a new address arrives at one and
sorts under everything ever asked for twice, so a full list would otherwise
drop it again on every request and never learn about the page that broke today.
**Forget all** empties it; the next request for one of them puts it back.

The switch also governs the per-rule counter, because they are the same cost: a
file write on a request that would otherwise have touched nothing. On a site
under heavy crawling, switching it off leaves the rules working and the panel
showing what it already has.

## The panel

Two screens under one strip, and the count beside the second is what makes
somebody look at it.

**Redirects** is the rules, as a table: what each answers, where it sends, its
kind, whether it covers a subtree, how often it has been followed and when it
last was. Edit and Delete per row, **New redirect** above them, and under the
table a probe. Editing an address onto one another rule already answers is
refused, naming it: that other rule would otherwise go away with its hits, and
a rule that silently went away is a redirect somebody believes is in place.

The probe is the half that earns its keep. A redirect is invisible until
somebody follows one, and a rule that does not fire looks exactly like a rule
that is not there. It asks the same question the live request asks, in the same
order, and answers one of three things: a page answers this address, so no rule
is consulted; this rule answers it and sends there; or nothing answers it, and a
visitor gets the 404 page.

**Addresses with no answer** is the second list, most asked for first, with one
button per row that opens the editor with the address already in it - what is
missing is the target, and that is the only thing anybody actually has to decide.
Saving a rule for an address takes it off that list, because it is answered now.

## The file

`data/redirects.php`, written by the panel:

```php
return [
    'format'  => 1,
    'rules'   => [
        [ 'from' => '/old/page', 'to' => '/new/page', 'status' => 301, 'subtree' => false, 'hits' => 12, 'last' => '2026-09-12 10:14:02' ],
        [ 'from' => '/shop',     'to' => '/store',    'status' => 301, 'subtree' => true,  'hits' => 0,  'last' => '' ],
    ],
    'misses'  => [
        '/old/press' => [ 'count' => 7, 'last' => '2026-09-12 09:58:41' ],
    ],
];
```

Editing it by hand is fine. Everything in it is held against what a rule may
be - and what an entry under `misses` may be - before any of it is used, and
what had to be dropped from the rules is named at the top of the panel rather
than swallowed: a rule that silently went away is a redirect
somebody believes is in place. A rule that would send a visitor back into itself
- `/a` to `/a`, or `/a/*` to something under `/a` - is refused on the way in,
because this feature only fires where nothing answers, so a target nothing
answers either comes straight back through the same rule.

## Tests

`tests/redirects-smoke.php` - the manifest and activation, what a path and a
target may be, the order rules are matched in, the loop guard, a redirect on an
address with no route, the silence on one that has a route, the subtree
remainder, the project directory in the `Location`, `POST` left alone, the
recording of a miss and the shapes it refuses, a list somebody edited by hand
and one that is already full, and every panel action including the probe's
three answers.

`tests/redirects-js-smoke.js` - what the panel's script does over a dom
stand-in: which of the two screens is on, that the strip travels with it, that
the rules table and the probe are gone while the addresses are up, and that
making a rule out of an address opens the editor with it already in.

Run them against a Nino checkout:

```sh
NINO_ROOT=../nino php features/Redirects/tests/redirects-smoke.php
node features/Redirects/tests/redirects-js-smoke.js
```

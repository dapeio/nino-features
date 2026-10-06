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
| **Sends to** | A path of this site - the home page, `/`, among them - or an `https://` address of another one. `http` is refused: a redirect is the one moment a site chooses the next address for somebody, and choosing a plaintext one hands that request to whoever is on the wire. |
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

## Targets

A rule is saved whatever it sends to - a rule for a page that is not made yet is
how a move is prepared - but the panel checks the target against what the site
has and says so, in the order a request is asked - the web server first, so a
file of the public directory comes before any route or rule:

| What answers the target | The panel says |
| --- | --- |
| a file in the project's public directory (`/public/…`) | nothing: the web server answers it as it stands, and a rule for that address is never reached |
| another site (`https://…`) | nothing. It is never fetched - what is there is that site's, and this feature makes no network request |
| a route of this site | nothing |
| another rule | a note: visitors are redirected twice. Where that rule's own target is answered by nothing, it is the next row |
| nothing | a warning: whoever follows the rule lands on the 404 page until a page, a route or a rule answers it |
| a chain of rules that comes back to where it was | a warning: a browser stops such a chain with an error |

The answer is read off the routes and the rules every time and is never
stored, so a page created or removed later changes it without a rule being
touched. It stands in the **Target** column of the table, for the rules that
need a second look, and after a save the warning stands on the message line.
**It warns and never refuses**; a rule that sends an address to itself is still
refused, as before.

Two things the check does not see:

- **A wildcard route answers everything below itself.** A rule to
  `/blog/old-post` is answered while the Posts feature's `GET://blog/*` is
  there, whether or not a post is - which is the same reason a moved post is
  that feature's to redirect, not this one's.
- **A subtree rule is answered by any route below its target**, since a page or
  a wildcard there answers the pages that moved. It does not say whether every
  one of them has one.

The **home page** is a valid target: an old address that moved to the front page
is the commonest rule there is. A subtree rule to `/` keeps the rest of the
path - `/old/x` goes to `/x` - and `/old` itself goes to `/`, with the project
directory in front where the site does not stand at the root. `/` is still never
an old address, since a rule from it would take the whole site.

The editor offers the pages of the site under the target field - every GET route
shaped like a page, named the way the menu names it and by its path where
nobody did - so a target does not have to be typed from memory. The field stays
what decides: an address of another site, or a page that is not made yet, is
typed.

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
somebody look at it. The strip stands in the head the workbench renders over
the panel, beside its name; on a kernel without that head it opens whichever
screen is on.

**Redirects** is the rules, as a table: what each answers, where it sends and
whether anything answers that, its kind, whether it covers a subtree, how often
it has been followed and when it last was. Edit and Delete per row,
**New redirect** above them, and under the table a probe. Editing an address
onto one another rule already answers is refused, naming it: that other rule
would otherwise go away with its hits, and a rule that silently went away is a
redirect somebody believes is in place.

The probe is the half that earns its keep. A redirect is invisible until
somebody follows one, and a rule that does not fire looks exactly like a rule
that is not there. It asks the same question the live request asks, in the same
order, and answers one of three things: a page (or a file of the public
directory) answers this address, so no rule is consulted; this rule answers it
and sends there; or nothing answers it, and a visitor gets the 404 page. It
asks the same question the table's flag and the warning after a save do, so the
three never disagree.

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
and one that is already full, the home page as a target and the subtree
remainder that leads to it, which pages a target can be picked from and how they
are named, what answers a target (a route, a rule, a loop, a file, another site
or nothing), and every panel action including the warnings of a save, the answer
each rule carries and the probe's three answers. Where node is on the path it
runs the script's suite below as well.

`tests/redirects-js-smoke.js` - what the panel's script does over a dom
stand-in of the pane and its head: which of the two screens is on, that the
strip goes into the head beside the panel's name and a redraw puts it back
there instead of beside the one before, that it travels with the screen that
is on where there is no head, that the rules table and the probe are gone while
the addresses are up, and that making a rule out of an address opens the editor
with it already in, that a target can be picked from the pages of the site, that
a rule whose target leads nowhere carries a flag in the table and that what a
save warned about stands on the message line while the editor is closed.

Run them against a Nino checkout:

```sh
NINO_ROOT=../nino php features/Redirects/tests/redirects-smoke.php
node features/Redirects/tests/redirects-js-smoke.js
```

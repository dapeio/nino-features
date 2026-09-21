# Changelog

All notable changes to the Newsletter feature are documented in this file.
A release is the tag `newsletter-<version>` of dapeio/nino-features.

## Unreleased

- Needs Nino `^1.3`, where the constraint said `^1.0`. The sectioned `manual`
  map this manifest carries is only read by a kernel newer than the
  `v1.2.0-beta` tag - `Features::manifest()` refuses it on the tagged one -
  and `^1.0` is satisfied by that kernel, so the catalogue offered the
  feature to an installation that could not then install it. `^1.3` names
  only a kernel that can read the manifest.

### Fixed

- **The README named the wrong unit for the confirmation mail's reply
  address.** It said `[[/form/email/owner]]` is "the fill the Form module's
  unit writes", which was true before Nino 1.3.0 and is why a project without
  the contact form had none. The base unit ships it since, as
  `[[/company/email]]`, so every install has one; and where a project has
  neither, `Mail::send()` drops the header and records why rather than sending
  a Reply-To naming a fill. The README says both now, and the suite holds the
  feature to them - the chained fill really resolving to an address, and a
  mail that still goes out with no Reply-To where nothing installed one. No
  code changed.

- **The subscriber list grew without an end, from requests anybody can
  send.** The signup endpoint is public and unauthenticated, and
  `\Nino\Filesystem::mutate()` rewrites the whole file on every post - but an
  unconfirmed entry had neither an expiry nor a ceiling. Measured on 2000
  posts with distinct addresses: 404 KB stored, 2000 pending, none of them
  expiring, and the cost of one signup up from 0.97 ms to 5.87 ms because each
  one reads and rewrites everything before it. That is quadratic, and nothing
  in front of it counts requests - the per-ip cap in `\Nino\Mail` throttles
  the confirmation mail, which is sent after the entry is already written.

  An unconfirmed signup expires now, after seven days or whatever
  `/nino/newsletter/pending-days` says; a confirm link that has sat unclicked
  for a week is not going to be clicked, and `\Nino\Mail`'s own rate-limit
  file drops its elapsed keys on write for the same reason. Under the expiry
  there is a ceiling of 500, because a burst arrives faster than a week
  passes: past it the oldest unconfirmed entry makes room for the newest. The
  same 2000 posts now leave 500 entries and 101 KB, at a flat 2.8 ms.

  A confirmed subscriber is never touched by either rule, and neither is an
  entry from before the double opt-in flow. Under a flood a visitor's own
  pending signup can be pushed out - they sign up again. Refusing new signups
  while the list is full would be the other way round: anybody could close the
  form for everybody.

- **The export carried every subscriber's unsubscribe token.** `newsletter/
  list` answered with the stored entries as they are, and
  `Nino.admin.exportCsv()` writes the union of every row's keys - so the file
  an operator opens in a spreadsheet, mails around and hands to a sending
  provider had a `token` column. A token is not a field: presented as
  `?unsubscribe=<token>` on the public route it takes that address off the
  list, and as `?confirm=<token>` it confirms a signup, both with nothing else
  to show. Whoever held that file could unsubscribe the whole list.

  The panel never drew the token and deletes by address, so it is simply not
  sent any more. It stays stored, or no link in a mail already sent would work
  again. The `ip` stays in the list too: it is the record of a consent, which
  is what it was stored for, and it does not let anybody act.

- **A signup recorded the proxy's address as the subscriber's.** The `ip`
  field of a pending entry came from `\Nino\Http::getClientIp()` without the
  app data it needs to resolve one, so behind a reverse proxy every signup
  carried the same address - the one thing that field exists to tell apart.
  It passes `$appData` now, so a project that named its proxies under
  `/nino/http/proxies` records the visitor behind them. Needs the kernel
  change that added the key.

- **A post or a link whose value is an array was a 500.** `email[]=x` on the
  signup, and `?confirm[]=x` or `?unsubscribe[]=x` on the confirm page,
  reached a `(string)` cast; the "Array to string conversion" warning that
  raises is a level the kernel treats as fatal, so an address anybody can
  visit answered 500. The signup reads its two values as strings or not at
  all (an array in the honeypot reads as a filled honeypot, which is what it
  is), and a token that is not a string is no token - the "invalid" page, the
  same as a token that does not match.

- **The address is cut on a character boundary.** The length cap used
  `substr()`, which splits a multibyte character when the cut lands inside
  one. An address that long is refused either way; what changes is that the
  value the refusal looks at is still text.

## 1.0.0 — 2026-09-07

- Moved unchanged from dapeio/nino 1.0.0-beta, where it lived under
  `app/Nino/Modules/Newsletter/` and then `features/Newsletter/`: the
  double opt-in signup under `/.newsletter`, the confirm and unsubscribe
  links, the removal record with the restore merge, and the Newsletter
  panel.
- Versioned here from now on, with its own `version` in `feature.php`,
  this changelog and the `nino` constraint `^1.0`.

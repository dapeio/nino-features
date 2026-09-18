# Changelog

All notable changes to the Redirects feature are documented in this file.
A release is the tag `redirects-<version>` of dapeio/nino-features.

## Unreleased

- Needs Nino `^1.3`, where the constraint said `^1.2`. The sectioned `manual`
  map this manifest carries is only read by a kernel newer than the
  `v1.2.0-beta` tag - `Features::manifest()` refuses it on the tagged one -
  and `^1.2` is satisfied by that kernel, so the catalogue offered the
  feature to an installation that could not then install it. `^1.3` names
  only a kernel that can read the manifest.

### Fixed

- **The panel said what it had dropped in English, whatever language it was
  set to.** The notes under the rules - a rule without a usable from and to, a
  second rule for one address, a status that is not a redirect, a rule that
  would loop - were composed as English sentences in `Rules::normalize()` and
  printed verbatim. So was the refusal for a status that is not a redirect.

  `Rules` answers with a fill key and what to put in it now, and the panel
  resolves it in the session locale, which is where that decision already
  lived (`Admin::_say()`). What a stored file is held to is `Rules`' business;
  which language the workbench says it in is the panel's. `loops()` returns
  the key of its reason rather than a sentence, because a reason is a fill
  too - the note carries it under `%r` and the panel resolves that first.

## 1.0.0 — 2026-09-12

First release: old addresses that still work, and a list of the ones that do not.

### Rules

- A rule is an old path, a target and a status. `301` for moved for good, `302`
  for moved for now, and nothing else - `307`/`308` are for a request with a
  body, and this only ever answers `GET` and `HEAD`.
- A rule can cover one page or a whole subtree, where what stood after the old
  prefix stands after the new one. An exact rule wins over a subtree one, and
  among subtree rules the longest prefix wins.
- A target is a path of this site or an `https://` address of another one.
  `http` is refused rather than passed through.
- A rule that would send a visitor back into itself is refused on the way in.
  This only fires where nothing answers, so a target nothing answers either
  comes straight back through the same rule, and the browser gives up in a way
  nobody can act on.

### Only where nothing else answers

- A rule is never consulted for a path that has a route, so a mistyped rule
  cannot take a working page off the site. Asked of `\Nino\Http::requestRoute()`
  rather than read off the status code, because a project's own `/404` route can
  answer with any status it likes.
- Which also means a wildcard route answers everything below itself: a moved
  post under the Posts feature's `/blog/*` is that feature's to redirect.

### The addresses nothing answered

- A request that found neither a route nor a rule is written down - the path
  only, not who asked for it. A list of addresses to fix is not a visitor log,
  and the file is in every backup.
- Only page-shaped paths: nothing below `/_` or `/.` , and no dot in the last
  segment unless the name ends in `.html`/`.htm`. Everything else reaching a
  404 on a public site is a scanner looking for `wp-login.php` and `.env`.
- At most 50 are kept, the most asked for first, and one button per row turns
  one into a rule with the address already filled in.
- **Remember what happened** switches both this and the per-rule counter off,
  because they are the same cost: one file write on a request that would
  otherwise have touched nothing.

### The panel

- Two screens: the rules with their editor, and the addresses with no answer.
- A probe under the rules that asks the same question a live request asks, in
  the same order, and says which of the three things would happen - a page
  answers it, a rule answers it, or nothing does. A redirect is invisible until
  somebody follows one, and a rule that does not fire looks exactly like a rule
  that is not there.

# Changelog

All notable changes to the Newsletter feature are documented in this file.
A release is the tag `newsletter-<version>` of dapeio/nino-features.

## Unreleased

- **The panel uses the workbench's request helper and its wording of a failure,
  where the Nino has them.** Every request goes through `Nino.adminUi.api`,
  which signs the page in again over what is on screen when the session has
  ended instead of losing it. The panel holds nothing to save, so nothing is
  registered with the shell's question about unsaved input. On a Nino without
  the helper (1.3.x) the panel posts as it did, but from the project's own
  directory: it posted to `/_admin/` from the root of the domain, which a
  project in a subdirectory does not answer. `nino` stays `^1.3`.

- Needs Nino `^1.3`, where the constraint said `^1.0`. The sectioned `manual`
  map this manifest carries is only read by a kernel newer than the
  `v1.2.0-beta` tag - `Features::manifest()` refuses it on the tagged one -
  and `^1.0` is satisfied by that kernel, so the catalogue offered the
  feature to an installation that could not then install it. `^1.3` names
  only a kernel that can read the manifest.

### Added

- **It brings its section of the privacy policy.** The install unit adds the
  section `newsletter` (double opt-in, what is stored, the seven days an
  unconfirmed sign-up is kept, the hash that remains after unsubscribing) to the
  type `privacy` of Nino 1.4's Legal module - add-only, and a Nino without the
  module ignores the file. The section names seven days, the default of
  `/nino/newsletter/pending-days`: whoever changes it changes the section too.
  It is a starting point, not legal advice - see "Legal" in Nino's
  `docs/development.md`.

- **A way out without a link.** A BCC mail cannot carry a personal unsubscribe
  link, and a subscriber may have lost the one they had; until now the only
  way off the list was a link nobody could ask for again. `/.newsletter/
  unsubscribe` is a page with a form (`GET`) and the post behind it (`POST`):
  the visitor enters the address and gets a mail with the link, and visiting
  the link unsubscribes as before - the request itself removes nothing. The
  answer is the same for every address and for every outcome of the mail: a
  page that says a link is on its way if the address is on the list, `200`
  whether the address is known or not, whether the mail went out, was refused
  or hit the cap - never a `429`, never a `500`, which would tell a known
  address from an unknown one. Two differences remain, neither in the answer:
  the time the request takes (a mail costs a `mail()` fork or an SMTP session,
  no mail nothing), and the per-ip mail cap, which only a sent mail charges
  and which the signup answers with `429`. Equalising the cap would need a
  public kernel API for it. A filled honeypot is a `418`, an address that is
  none a `400` with the form and its error line, a post the CSRF check refused
  is left alone. An entry without a token (written before the double opt-in
  flow) gets one in the same write. The panel shows the address of the
  page beside the BCC line, to put into every BCC mail. New: the routes
  `GET/POST://.newsletter/unsubscribe`, the templates
  `page-newsletter-unsubscribe.tpl` and `mail-newsletter-unsubscribe.tpl`, the
  text keys `/newsletter/unsubscribe/*`, `/newsletter/page/unsubscribe-requested/*`
  and `/mail/newsletter/unsubscribe/*`, the config keys
  `/nino/newsletter/unsubscribe-template` and `/nino/newsletter/
  unsubscribe-mail-template`, and the blacklist entries
  `/newsletter/unsubscribe/url` and `/newsletter/unsubscribe/error`. **The
  install unit is applied add-only:** a project that activated the feature
  earlier gets the two templates and the keys when it activates the feature
  again (the Features panel's update); a template or key it already has stays.
  The form carries neither `.nino-form` nor `.nino-newsletter-form`, which the
  kernel script binds to an xhr handler that would show the contact form's
  success text.

### Changed

- **The README names the signup preset's real action, and the comments the
  kernel methods there are.** The `form-newsletter` preset posts to
  `[[/nino/dir]]/.newsletter`, not `/.newsletter`, and is there only where
  the Templates feature is installed, which this feature does not require.
  The class pointed at `Form::_record()` and `Form::callbackResponse`, which
  are `\Nino\Form::record()` and `\Nino\Form::handle()`; the panel's count
  is read by `summary()`, not by a `Dashboard::apiSummary`. Words only.

- **The panel asks the feature for its subscriber file and its removal
  record.** `Newsletter\Admin` kept its own copies of `/data/newsletter.php`
  and `/data/newsletter-removed.php` and wrote the removal record itself, with
  a fourth copy of the hash rule, justified by an autoload hazard that cannot
  occur - the panel exists only once the feature's class is loaded. It reads
  `Newsletter::PATH` now and records a deletion through
  `Newsletter::recordRemoval()`, which answers whether the record was written;
  the hash is computed in one place. A delete whose record cannot be written
  still answers 500 and leaves the subscriber on the list.

- **The text keys follow Nino's grammar, and the unit no longer carries the look
  of the mails.** The words of the feature are `/feature/newsletter/...`:
  `label/submit`, `info/{required,email,success,error}` (the `existing` text,
  which nothing read, is gone), `subject/confirm` and `subject/unsubscribe` (the
  mails' subjects),
  `result-{confirmed,unsubscribed,invalid,unsubscribe-requested}/{title,text}`
  (the outcome the code chooses, `/newsletter/page/<result>/...` before) and the
  fills the class sets at request time - `page/title`, `page/text`,
  `confirm/url`, `unsubscribe/url`, `unsubscribe/error` - blacklisted as before.
  The words of the templates it copies are theirs:
  `/template/page-newsletter-unsubscribe/{intro/title,intro/text,form/submit}`,
  `/template/mail-newsletter-confirm/{intro/title,intro/text,action/button,outro/notice,outro/closing}`
  and the same under `/template/mail-newsletter-unsubscribe/`. The email field's
  label is the base unit's `/template/common/form/email`, which the unit no
  longer writes. `install/text/global.php` and the 11 `/mail/style/*` entries of
  the blacklist are gone: the look of the mails is the base unit's,
  `/project/mail/{color,font,spacing}/*`, which every project has.
  `mail-header.tpl` and `mail-footer.tpl` are the Form module's files byte for
  byte (the logo is the image slot `[image /logo]`). What the templates and the
  class read of the kernel is `/project/website/general/url`,
  `/project/company/general/name`, `/project/mail/address/owner`,
  `/project/website/html/{lang,charset}` and `/_nino/webpage/home/{uri,name}`.
  Nino 1.4.0 renames the keys the feature reads and it is renamed with them, so
  `nino` is `^1.4`: a Nino before 1.4 has neither the words nor the keys, and is
  not offered the feature. A project that has the unit's old files copies them
  by hand: the unit is add-only and does not re-apply. Nothing has been
  published under the old keys, so there is no migration; a project that already
  has texts under them copies the values to the new keys.

- **The signup is answered by what happened to the mail, and an address that
  is subscribed already is mailed too.** The answer used to be `200` whatever
  came of the mail - a visitor was told to check the inbox for a mail the
  server never sent, with a host that has no `mail()`, a refusing transport or
  the cap of five mails an hour per ip. It is `200` when the mail went out,
  `429` when `\Nino\Mail::send()` refused it for the cap, and `500` when it
  could not be sent or the entry could not be stored (a list that cannot be
  locked or written used to read as "already subscribed"); the page shows its
  generic `/newsletter/info/error` for the last two. For that to tell nothing
  about who is on the list, every address takes the same way: an address that
  is subscribed already is no longer answered without a mail - a `429` or a
  `500` that only some addresses could reach would be a free test of whether an
  address is subscribed. It gets the confirmation mail again, with the token it
  has; its status and date stay as they are, confirming that mail changes
  nothing, and the signup does not clear its removal record. A legacy entry
  without a token gets one, and confirming that mail records it as `subscribed`
  and keeps the date it signed up on. The flag `./nino/mail/ratelimited` is sticky, so
  it is unset before the send. Note that the shipped mail's line "you will not
  receive any newsletter" does not describe that case; `/mail/newsletter/
  notice` is the project's to word.

- **A pending signup is no subscriber.** The panel's count, the Dashboard tile
  and the BCC line took every entry on the list, so an address that never
  confirmed - and so never agreed to a newsletter - was counted and put into
  the mails. They are the confirmed addresses now. The list answers a `status`
  for every row (`pending`, or `subscribed` - an entry from before the double
  opt-in flow has none and reads as subscribed), `counts` and the address of
  the unsubscribe page; the panel shows the status in a column, names the
  pending ones beside the count (`12 subscribers · 3 pending`), and filters
  by it - All, Confirmed, Pending, All being the default. The CSV export
  follows the filter, so what is on screen is what is written; it had been tied
  to the BCC rows. The README's pointer to the signup form preset named a
  directory that does not exist; it is `features/Templates/library/
  form-newsletter/`.

- **German install texts say Du.** Newsletter's German words said Sie and
  Ihr; they read "Du", "Dein" and "Dich" now, with the verbs and the
  imperatives in step. Keys: `/newsletter/info/required`, `email`, `success`
  and `error`; `/mail/newsletter/subject`, `intro`, `notice` and `closing`;
  `/newsletter/page/confirmed/text`, `/newsletter/page/unsubscribed/text` and
  `/newsletter/page/invalid/text`; English is unchanged. The unit is applied
  add-only: a project that activated the feature keeps its wording, activating
  it again does not replace a key it has, and it changes the words in the Text
  panel. A new activation gets the new ones.

- **The panel's script still described the module this used to be.** Its
  docblock called itself `editor.js`, pointed at a
  `Modules\Newsletter\Editor` that has never existed here, and said there is
  "deliberately no self-service unsubscribe" and that its Delete button "is
  the only way an entry is ever removed" - the feature's own headline is the
  unsubscribe link, and `Admin/Admin.php` beside it says so. `admin.css` named
  itself `editor.css` the same way, and `Newsletter.php` twice sent a reader
  to `_admin/Editor.php` for a panel that sits in `Admin/Admin.php`. The
  README had the panel in the Content group and its permission offered under
  Content, where a panel a feature brings lands under Features whatever
  `nav()` names, and left `tests/` out of the directory listing. Words only -
  the code is unchanged.

### Fixed

- **A BCC line that could not be copied was reported in green.** The panel
  marked the failure with a class no stylesheet defines, and the id rule that
  colours the "copied" note would have kept it green over the workbench's
  error class anyway. The note carries `nino-admin-error` on a failure now and
  the panel's stylesheet gives it the error colour; a copy that works clears
  it again.

- **The link of a confirmation or unsubscribe mail stayed in the fills.** It was
  added for every language to render the mail and never taken out, so it was
  there for whatever else the request rendered afterwards. It is removed right
  after `Mail::send()`, also when the send throws.

- **Confirming an entry from before the double opt-in flow replaced its date.**
  An entry with a token but no status counts as subscribed since the day it
  signed up; confirming its mail set the date to the day of the confirmation.
  Only the status is set now.

- **The Text panel reported three keys of this feature as missing.**
  `/newsletter/page/title`, `/newsletter/page/text` and
  `/newsletter/confirm/url` stand in the page and the confirmation mail, the
  class fills them at request time, and no text file ever answers them - so
  the panel's scan for missing keys listed the three on every project with
  this feature, and the Dashboard counted them, as gaps nobody could close.
  The install unit lists them under `blacklist` now, beside the eleven
  `/mail/style/*` keys, which is what the scan skips. A project that
  activated the feature before this entry ignores the three once in Text →
  Keys, or activates the feature again; the suite holds that the scan reports
  no key of this feature.

- **Two checks watched a path the subscriber file is never written to.**
  `is_file( \Nino\Filesystem::getPath( $appData ). '/data/newsletter.php' )`
  looks under the project root, and `/data` is a private directory: the file
  lands under the private root, which is where the check three lines further
  down already looked. So "none of the rejected signups created the newsletter
  file" and "a csrf-blocked signup does not create the newsletter file" were
  true of every possible run. Both ask `\Nino\Filesystem::path()` now, and
  the dead `$pendingPath` that carried the same mistake further down is gone.

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

### Removed

- **The setup wizard's keys in the install unit.** `label`, `moduleClass` and
  `requiresModules` were never read by an activation, and the wizard does not
  offer features. A project's files are unaffected.

## 1.0.0 — 2026-09-07

- Moved unchanged from dapeio/nino 1.0.0-beta, where it lived under
  `app/Nino/Modules/Newsletter/` and then `features/Newsletter/`: the
  double opt-in signup under `/.newsletter`, the confirm and unsubscribe
  links, the removal record with the restore merge, and the Newsletter
  panel.
- Versioned here from now on, with its own `version` in `feature.php`,
  this changelog and the `nino` constraint `^1.0`.

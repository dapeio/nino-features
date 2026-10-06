# Changelog

All notable changes to the Mailer feature are documented in this file.
A release is the tag `mailer-<version>` of dapeio/nino-features.

## Unreleased

- **The panel uses the workbench's request helper and its wording of a failure,
  where the Nino has them.** Every request goes through `Nino.adminUi.api`,
  which signs the page in again over what is on screen when the session has
  ended instead of losing it; a failed test mail reads the way the workbench
  says it, with the reason the mail server gave as before. The test address is
  not stored, so nothing is registered with the shell's question about unsaved
  input. On a Nino without the helper (1.3.x) the panel posts as it did, but
  from the project's own directory: it posted to `/_admin/` from the root of the
  domain, which a project in a subdirectory does not answer. `nino` stays
  `^1.3`.

- The README says where the settings are: in the feature's **Settings** - the
  Features panel's form for it, or a **Settings** tab of the Mailer panel on a
  Nino that has one.

- **A non-ASCII subject went out as one encoded word of any length, and an
  encoded display name went out inside quotes.** `_encodeHeaderValue()`
  base64'd the whole value into a single `=?UTF-8?B?...?=`, where an encoded
  word may be 75 characters and no more (RFC 2047) - an ordinary German
  subject passes that without being long, and a longer one passes what a
  header line is meant to keep to as well. Its own docblock said it used "the
  same encoding `\Nino\Mail::send()` uses", and now it does: the value goes
  through `mb_encode_mimeheader()`, the call the kernel makes, which splits it
  into words that fit and folds between them. `_fromHeaderValue()` also put
  the quotes of a display name around the encoded word, which an encoded word
  may not stand inside (RFC 2047 section 5) - a reader that takes the quotes
  at their word shows the site owner `=?UTF-8?B?...?=` where the name should
  be. An encoded name goes in unquoted now; a plain ASCII one keeps its
  quotes, which is what lets it carry a comma.

- **The kernel's envelope sender is named as the textfill it is now.** Nino
  1.3.0 reads `[[/mail/sender]]` from the Text panel where it read
  `/nino/mail/sender` from `config.php` before; the message a send without any
  From address fails with names the fill, and the test sets the sender both
  ways, so it answers the same against either kernel.

- Needs Nino `^1.3`, where the constraint said `^1.1`. The sectioned `manual`
  map this manifest carries is only read by a kernel newer than the
  `v1.2.0-beta` tag - `Features::manifest()` refuses it on the tagged one -
  and `^1.1` is satisfied by that kernel, so the catalogue offered the
  feature to an installation that could not then install it. `^1.3` names
  only a kernel that can read the manifest.

### Added

- **It brings its section of the privacy policy.** The feature has an install
  unit now, which carries nothing but the section `mailer` (the mail server of
  an email service provider, who receives what) for the type `privacy` of Nino
  1.4's Legal module - add-only, and a Nino without the module ignores the file.
  The provider is named as a category, not by name: the operator adds who it is.
  It is a starting point, not legal advice - see "Legal" in Nino's
  `docs/development.md`.

- **The test mail's address is filled in, and the last errors are in the
  panel.** The address field starts with the signed-in account's own address
  (the From address where that is none) instead of an empty box, and is never
  written over once somebody typed in it. Under the form the panel lists the
  last five failed sends, newest first, with their dates, as text - or says
  there were none; a failed test mail adds itself to it at once. A failed send
  was only ever a line in the runtime's error log, which no screen shows, and a
  reason on the test button for the one request that had it. They are kept in
  `/data/mailer.php`, a ring of five `{ date, reason }` that a delivery never
  touches and the password is never in - the manifest lists it under `data`, so
  a backup carries it. A reason can name an address the server refused, as the
  log's line does. The status line shows the port a send really connects to.

### Changed

- **The text fill it names is `/project/mail/address/envelope`.** The message it
  logs when no From address is available names
  `[[/project/mail/address/envelope]]`, the envelope sender of Nino 1.4
  (`/mail/sender` before). Nino 1.4.0 renames the keys the feature reads and it
  is renamed with them, so `nino` is `^1.4`: a Nino before 1.4 has neither the
  words nor the keys, and is not offered the feature. Nothing has been published
  under the old keys, so there is no migration; a project that already has texts
  under them copies the values to the new keys.

- **The port comes from the encryption.** `port` is `0` by default and `0`
  means "from the encryption": 587 for STARTTLS, 465 for TLS from the start, 25
  for none; any other number is used as it is. It was 587 whatever the
  encryption said, so choosing `tls` and forgetting the port sent TLS to 587, a
  timeout with nothing in it that said why. A failed send now says it when the
  port and the encryption are a pair that does not go together - 587 or 25 with
  `tls`, 465 with `starttls` - and how to fix it, whatever the failure was
  (STARTTLS against 465 connects fine and then waits for a greeting that never
  comes). **`upgrade()` migrates the projects that saved the settings:** the
  Features form posts every field, so every configured installation holds an
  explicit 587, and "from the encryption" would never have reached it. A stored
  port that is its encryption's own standard one (587 with STARTTLS, 465 with
  TLS, 25 with none) is set to 0 - the same port, following the encryption from
  then on - and any other number stays. It is idempotent, and it runs when the
  feature is updated, which is when the version is bumped. A fresh installation
  never calls it.

- **German panel text says Du.** The test mail's body, `/_admin/mailer/mail/body`
  in `text/de_DE.php`, said "Ihrer Nino-Installation"; it says "Deiner" now.
  Panel texts are read live, so every project gets it with the update; English
  is unchanged.

- **The panel no longer names itself a second time.** The workbench opens
  every pane with a head that names the panel, so "SMTP mail delivery" stood
  one line under "Mailer" and said the same thing again. The screen opens
  with its hint now, the first line under the head. The fill
  `/_admin/mailer/label/title` is gone from both text files. A kernel from
  before the head draws no name over any pane, its own included, and the
  screen opens with the hint there too.

- **The README put the panel in a group it cannot be in, and pointed at a
  section that is not there.** A panel a feature brings lands under Features
  whatever `nav()` names - `\Nino\Admin\Panels` overrides the group so that
  granting it stays a bounded grant - and this panel names `system`, which is
  what the README repeated twice. The settings section also linked to
  `#the-panel`, an anchor no heading here produces, and the directory listing
  left out the `tests/` the README's own last section is about. Words only -
  the code is unchanged.

- **The transport reads its settings in one go.** `Features::setting()`
  answers one name by building every value the manifest declares - it reads
  the feature, then validates each stored value against its schema - and this
  transport asked nine times per mail. Measured at 0.0104 ms against 0.0012,
  which is nothing beside an SMTP round trip; nine reads of one thing is nine
  places for the ninth to be forgotten, which is the reason.

## 1.0.0 — 2026-09-08

- First release: a pure-php SMTP transport registered under
  `\Nino\Mail::TRANSPORT`, so every mail `\Nino\Mail::send()` hands out -
  the contact form, the Newsletter feature, a project's own code - goes out
  over SMTP instead of the server's `mail()`.
- Settings for host, port, encryption (STARTTLS, TLS from the start, or
  none), username, password, the From address and name, a timeout and
  certificate verification.
- A **Mailer** panel in the workbench's System group: a status line and a
  **Send test mail** button, going through the same transport and the same
  per-ip cap as every other mail on the site.

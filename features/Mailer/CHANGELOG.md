# Changelog

All notable changes to the Mailer feature are documented in this file.
A release is the tag `mailer-<version>` of dapeio/nino-features.

## Unreleased

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

### Changed

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

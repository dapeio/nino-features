# Mailer

**Key:** `mailer` · **Class:** `\Nino\Modules\Mailer` · **Version:** 1.0.0 · **Nino:** `^1.1`

Delivers every mail Nino sends - the contact form, the Newsletter feature,
anything that calls `\Nino\Mail::send()` - over SMTP instead of the server's
own `mail()`. Many hosts have no working `mail()`, or one that lands in spam
because it never authenticates; this feature makes outgoing mail reliable by
sending it itself, with a real login, to a real mail server.

One directory, the shape the [feature recipe](https://github.com/dapeio/nino/blob/main/docs/recipes/feature.md)
describes: `feature.php`, `Mailer.php`, `Smtp/Smtp.php`, `Admin/Admin.php`,
`assets/`, `text/`. No install unit - the feature has no page and no
template of its own, and the only mail it composes itself (the test mail's
subject and body) lives in the panel's own `text/`. The changes per version
are in [CHANGELOG.md](CHANGELOG.md).

## How it works

`init()` registers `Mailer::callbackSend()` under `\Nino\Mail::TRANSPORT`
(`/nino/mail/send`, see `_nino/Nino/Mail/Mail.php`), the one hook that lets a
module take over delivery: `\Nino\Mail::send()` calls it after its own per-ip
cap and after every header value is cleaned, with the subject still raw
UTF-8. The callback:

- leaves `sent` at `null` while `host` is empty, so `\Nino\Mail::send()`
  falls through to `mail()` exactly as if the feature were not active - an
  operator who switches it on before configuring it loses no mail;
- otherwise builds the complete CRLF message and sends it over a plain,
  pure-php SMTP client (`Modules\Mailer\Smtp`, no extension required beyond
  `openssl` for encryption), and sets `sent` to `true` on a `250` after
  `DATA`, `false` on any failure;
- never throws. A failure is recorded with `trigger_error( 'Mailer: ...',
  E_USER_WARNING )` - the reason, never the password - so it reaches the
  runtime's error log, and under `./mailer/last` for the current request, so
  the panel's test button can report it.

### The message it builds

Headers, in order: `Date` (RFC 2822), `Message-ID` (`<random@host>`, `host`
being the configured SMTP host), `To`, `Subject` - RFC 2047 `=?UTF-8?B?...?=`
when it is not plain ASCII, else literal - `MIME-Version: 1.0`, then the
kernel's own header block verbatim (`Content-Type: text/html; charset=UTF-8`
and, when the mail carries one, `From:`/`Reply-To:`). A `From:` of the
feature's own is added only when that block has none, built from the `from`
and `fromName` settings - the display name quoted, RFC 2047-encoded when it
is not plain ASCII. A blank line, then the body: normalized to CRLF and
dot-stuffed (a line starting with `.` gets a second one), ending with the
DATA terminator, CRLF `.` CRLF. The envelope sender (`MAIL FROM`) is always
whichever address ends up in `From:` - the kernel's own sender when the mail
has one, the `from` setting otherwise. `To` accepts mail()'s own
comma-separated list of plain addresses, one `RCPT TO` per address -
`\Nino\Mail::send()` itself only ever hands the transport a single address,
but a direct caller that puts several on one line gets them all delivered.

### The SMTP dialogue

`stream_socket_client()` with the configured timeout - `tls://host:port` for
implicit TLS, plain otherwise; peer verification (`verify_peer`,
`verify_peer_name`) is on by default through the stream context, the
`verify` setting turns it off for a development server with a self-signed
certificate. `EHLO`, falling back to `HELO` for a server that does not know
it; `STARTTLS` when `encryption` says so, followed by
`stream_socket_enable_crypto()` and a fresh `EHLO` over the now-encrypted
connection (some servers only advertise `AUTH` after the upgrade) - a
failed upgrade is a failure, never a silent fall-back to plaintext. `AUTH
PLAIN` when the server advertised it (one round trip), else `AUTH LOGIN`,
skipped entirely when `username` is empty (a local relay that needs none).
`MAIL FROM`, one `RCPT TO` per recipient, `DATA`, `QUIT`. A multi-line reply
(`250-...`) is read to its final `250 ...` line; any reply code outside
2xx/3xx is a failure carrying the server's own line as the reason.

## Settings

Configured in the Features panel, under `/nino/features` in `config.php`
once saved - there is no settings screen of the feature's own, only the
[panel](#the-panel) that proves them.

| Setting | Type | Rules | Default |
| --- | --- | --- | --- |
| `host` | string | maxlength 253 | `''` (not configured) |
| `port` | int | 1–65535 | `587` |
| `encryption` | select | `starttls` (STARTTLS, port 587), `tls` (TLS from the start, port 465), `none` (only for a local relay) | `starttls` |
| `username` | string | maxlength 200 | `''` |
| `password` | secret | maxlength 200 | - (never has a default, never shown again once saved) |
| `from` | email | used when a mail brings no `From:` of its own | `''` |
| `fromName` | string | maxlength 100, the display name beside `from` | `''` |
| `timeout` | int | 5–60 seconds | `15` |
| `verify` | bool | verify the server's certificate | `true` |

### Provider notes

- Port `587` with `starttls` is the usual choice; port `465` with `tls` (TLS
  from the connection's first byte) is the other common one. Plain `none`
  only makes sense against a local relay on the same host or network that
  needs no encryption and no login.
- `username` is usually the mailbox's full address, the same as `from`.
- `from` has to be an address the provider actually lets this account send
  as - most reject, or silently rewrite, a `From:` that is not one of the
  mailboxes or verified domains on the account.

## The test mail

`\Nino\Modules\Mailer\Admin` brings a **Mailer** panel to the workbench's
System group (`/_admin/mailer/manage`). One pane: a status line naming the
configured host, port and encryption - never the password, and "not
configured yet" while `host` is empty - an address field and a **Send test
mail** button, posting `mailer/test { to }`. The action validates the
address with `FILTER_VALIDATE_EMAIL`, then sends through the very same
`\Nino\Mail::send()` every other mail on the site goes through - so a
successful test mail proves the settings actually work, not just that they
parse. A failure answers `400` with the reason the transport itself
recorded; the kernel's own per-ip cap (5 mails per hour per client ip,
`_nino/Nino/Mail/Mail.php`) applies to the test mail exactly like any other,
and the panel says so explicitly rather than reporting a generic failure
when that is what refused it.

| | |
| --- | --- |
| Navigation | **Mailer** in the System group (uri `mailer`, position 35) |
| Permission | `/_admin/mailer/manage` on every action |
| Actions | `mailer/status` (`apiStatus()`): host/port/encryption, never username or password · `mailer/test` (`apiSendTest()`): one test mail |
| Activity log | `log()` writes `Send test mail to "<to>"` |
| Assets | `assets/admin.js`, named through `\Nino\Admin\Panels::relative()` so it moves with the directory |
| Text | `text/en_US.php`, `text/de_DE.php` - the panel's own words, and the test mail's subject/body |

## What is logged

Every failed send - real mail or the panel's test - calls `trigger_error(
'Mailer: <reason>', E_USER_WARNING )`, landing in the runtime's error log.
The reason names what went wrong (a connect failure, a refused command with
the server's own reply line) but never the password.

## Troubleshooting

| Symptom | Likely cause |
| --- | --- |
| "STARTTLS negotiation failed" | The server's certificate does not verify - check the host name matches the certificate, or turn `verify` off only for a development server you control |
| "AUTH PLAIN refused" / "AUTH LOGIN refused..." (535) | Wrong username or password, or the provider requires an app-specific password rather than the account's own |
| "MAIL FROM refused" / "RCPT TO ... refused" (550 or similar) | The `from` address is not one the provider allows this account to send as, or the recipient address itself is rejected |
| "could not connect to host:port" | Wrong host or port, a firewall in the way, or `encryption` set to `tls` against a port that expects `starttls` (or the other way round) |
| Send succeeds here but mail still does not arrive | Check the receiving side - spam folder, SPF/DKIM/DMARC on the `from` domain - this feature only proves the SMTP session succeeded, not that a filter downstream accepted the result |

## Tests

`tests/mailer-smoke.php` is the feature's own test: the manifest and the
activation through `\Nino\Features`, the transport's registration, the
"not configured" fall-through, a real SMTP session against a fake server run
as a child process (no network) - the dialogue, the built message, From
resolution, several recipients on one comma-separated address, `AUTH LOGIN`
as well as `AUTH PLAIN`, refusals (`RCPT` 550, `AUTH` 535), a dead port, and
the workbench panel with its permission. It loads Nino's `tests/harness.php`
from the checkout three levels up - or from the one `NINO_ROOT` names - and
defines `NINO_FEATURES_DIR` as this feature's parent directory:

```bash
php features/Mailer/tests/mailer-smoke.php
NINO_ROOT=../nino php features/Mailer/tests/mailer-smoke.php
```

With the feature copied into a checkout, Nino's `tests/features-smoke.php`
validates the manifest as well, and PHPStan analyses the class, the SMTP
client and the panel.

# Newsletter

**Key:** `newsletter` · **Class:** `\Nino\Modules\Newsletter` · **Version:** 1.0.0 · **Nino:** `^1.3`

Double opt-in newsletter signup, everything under the `/.newsletter` uri: a
visitor posts an address, receives a confirmation mail, and is on the list
only once the link in it is visited. The same per-subscriber token drives
the self-service unsubscribe link; a subscriber who has lost it asks for a new
one at `/.newsletter/unsubscribe`. The workbench gets a **Newsletter** panel
with the list and the status of every address, a copyable BCC line of the
confirmed ones, a CSV export and a delete. Sending the newsletter itself is not
part of the feature - that happens in a mail client or a project's own mailing
service; the panel gets the addresses there.

One directory, the shape the [feature recipe](https://github.com/dapeio/nino/blob/main/docs/recipes/feature.md)
describes: `feature.php`, `Newsletter.php`, `Admin/Admin.php`, `assets/`,
`install/`, `text/`, `tests/`. The changes per version are in
[CHANGELOG.md](CHANGELOG.md).

## Routes

`init()` registers its routes itself, so a project that switches the
feature on has a working flow without adding anything to `config.php`:

| Route | `uri` | Handler |
| --- | --- | --- |
| `POST://.newsletter` | `/.newsletter` | `callbackResponse()` on `/nino/http/response/POST://.newsletter` |
| `GET://.newsletter` | `/.newsletter` | `callbackAction()` on `/nino/http/response/GET://.newsletter`, body `[template /templates/page-newsletter]` |
| `POST://.newsletter/unsubscribe` | `/.newsletter/unsubscribe` | `callbackUnsubscribeRequest()` on `/nino/http/response/POST://.newsletter/unsubscribe` |
| `GET://.newsletter/unsubscribe` | `/.newsletter/unsubscribe` | `callbackUnsubscribeForm()` on `/nino/http/response/GET://.newsletter/unsubscribe`, body `[template /templates/page-newsletter-unsubscribe]` |

### `POST /.newsletter` - the signup

Reads `email` and `location` from the posted form. `location` is a honeypot
and has to stay empty. The handler respects a rejection by the kernel's CSRF
check (`./nino/csrf/blocked`), so the form has to render `[csrf]`.

| Answer | When |
| --- | --- |
| `400` | `email` missing, or not a valid address |
| `418` | the honeypot is filled |
| `200`, body `{ "status": "ok" }` | the confirmation mail went out |
| `429`, no body | `\Nino\Mail::send()` refused the mail: the client ip is at its cap of five mails an hour |
| `500`, no body | the mail could not be sent, or the entry could not be stored (the list could not be locked or written) |

The answer follows the delivery of the mail and nothing else. It is the same
whether the address is new, already pending or already subscribed, because the
form is public and a different answer would tell anyone whether a given
address is on the list - a `429` or a `500` that only some addresses could
reach would be as good a test as a `200` that only some got. So every address
takes the same way: a new one is recorded as pending with a fresh token and
gets the mail; a pending one gets its mail again with the same token, because
the first may never have arrived; an address that is **already subscribed** is
mailed too, with the token it already has - its status and its date stay as
they are, confirming that mail changes nothing, and the signup does not clear
its removal record. An entry from before the double opt-in flow, which has no
token, gets one in the same write; confirming that mail is the first
confirmation such an entry has, so it is recorded as `subscribed` with the
date of the confirmation. A recorded signup clears the address from
the removal record (see [Data and restore](#data-and-restore)) - a fresh signup
is a current consent. The page shows the generic `/newsletter/info/error` for
the `429` and the `500`; no text of its own is needed.

The shipped confirmation mail says "if you did not sign up, ignore this email -
you will not receive any newsletter". For an address that is subscribed already
the second half does not describe what happens; a project that minds words it
differently in `/mail/newsletter/notice`.

### `GET /.newsletter` - confirm and unsubscribe

| Query | Effect |
| --- | --- |
| `?confirm=<token>` | flips the pending entry to subscribed. Confirming an already confirmed token is not an error - a twice-clicked link stays friendly |
| `?unsubscribe=<token>` | removes the entry, subscribed or still pending, and records the removal |
| anything else, or an unknown token | nothing; the page answers with status `404` |

Every outcome renders `page-newsletter.tpl`. The handler adds two fills for
it - `[[/newsletter/page/title]]` and `[[/newsletter/page/text]]` - that
resolve to `[[/newsletter/page/<result>/title]]` and `…/text` with `<result>`
one of `confirmed`, `unsubscribed` or `invalid`, and those are ordinary
translated fills the unit writes into the project's `text/<locale>.php`.

Both links are absolute: `https://[[/website/url]]/.newsletter?confirm=<token>`
and `…?unsubscribe=<token>`. `[[/website/url]]` is the base fill the setup
wizard writes; it has to be the site's real host for the links to work.

### `/.newsletter/unsubscribe` - the way out without a link

A BCC mail cannot carry a personal unsubscribe link, and a subscriber may have
lost the one they had. `GET /.newsletter/unsubscribe` renders the form that
asks for the address (`page-newsletter-unsubscribe.tpl`, or the template
`/nino/newsletter/unsubscribe-template` names); `POST` reads `email` and the
`location` honeypot the way the signup does, respects the CSRF check, and:

| Answer | When |
| --- | --- |
| `400`, the form again with `/newsletter/info/email` as its error | `email` missing, or not a valid address |
| `418`, the answer page | the honeypot is filled; no mail |
| `200`, the answer page | in every other case |

The answer page is `page-newsletter.tpl` with `[[/newsletter/page/title]]` and
`[[/newsletter/page/text]]` pointing at
`/newsletter/page/unsubscribe-requested/title` and `…/text` ("if this address is
on the list, a link to unsubscribe is on its way"). It never depends on the
address or on the mail: not on whether the address is on the list, not on a
mail cap, not on a transport that refuses - never a `429`, never a `500`.
Nothing is removed by the request; the link in the mail is the proof that the
address is the asker's, and visiting it unsubscribes as before.

For an address on the list - pending or subscribed - the handler mails
`/templates/mail-newsletter-unsubscribe` (or the template
`/nino/newsletter/unsubscribe-mail-template` names), with
`[[/newsletter/unsubscribe/url]]` set to that entry's unsubscribe link, the
subject `[[/mail/newsletter/unsubscribe/subject]]` and `[[/form/email/owner]]`
as Reply-To. An entry without a token gets one in the same write. An unknown
address gets no mail.

Two differences between a listed and an unlisted address remain, and neither
is in the answer the request gets. One is time: a mail takes a `mail()` fork
or an SMTP session, no mail takes nothing. The other is the cap on mails per
client ip: only a mail that is sent charges it, and the signup answers `429`
at that cap, so one request here followed by a few signups could tell a
listed address from an unlisted one. The time is the cheaper of the two to
measure. Equalising the cap would need a public kernel API for charging it,
which `\Nino\Mail` does not have.

The Newsletter panel shows the absolute address of this page beside the BCC
line: put it into every newsletter you send by BCC.

### The signup form

The feature ships no signup form. The Templates feature does: the `form-newsletter`
section preset under `features/Templates/library/form-newsletter/`
renders a `form.nino-newsletter-form` with `action="/.newsletter"`, `[csrf]`,
the `location` trap and the `email` field, and `_nino/Nino.ui.js` submits it
by xhr and shows the outcome. The words that form and script read are what
the install unit writes: `/newsletter/label/email`,
`/newsletter/label/submit`, `/newsletter/info/required`,
`/newsletter/info/email`, `/newsletter/info/success` and
`/newsletter/info/error`, which is what the page shows for a `429` and for a
`500`. `/newsletter/info/existing` is written as well but never shown by the
shipped handler, because the endpoint does not distinguish the case. A form
of your own posts the same two fields to the same uri.

### The confirmation mail

`_sendConfirmMail()` renders the template `/nino/newsletter/confirm-template`
names - `/templates/mail-newsletter-confirm` by default - with the fill
`[[/newsletter/confirm/url]]` set to the confirm link, in the visitor's
current locale, and sends it through `\Nino\Mail::send()` to the address,
with `[[/mail/newsletter/subject]]` as the subject and `[[/form/email/owner]]`
as Reply-To. `[[/form/email/owner]]` is the **base** install unit's fill,
shipped as `[[/company/email]]` - the mailbox the project already named, so
every install has one and the newsletter's own unit writes none. (Before Nino
1.3.0 it belonged to the Form module's unit, which the wizard offers rather
than always installing, so a project running this feature without the contact
form had no such fill at all.) Where it does not resolve to an address,
`Mail::send()` drops the header and records why rather than sending a Reply-To
naming a fill - the mail still goes out, because the recipient and the body
were never the problem. `Mail::send()` rate-limits per client IP, and the flag
it leaves behind when it refuses (`./nino/mail/ratelimited`) is unset before
every send of this feature, so a refusal is always this send's own. Whether the
mail went out is what the visitor is answered (see above); the pending entry is
recorded either way, and submitting again resends it.

## `getUnsubscribeLink()`

```php
\Nino\Modules\Newsletter::getUnsubscribeLink( array &$appData, string $email ): string|false
```

The absolute unsubscribe url for a subscribed or still pending address, or
`false` for one that is not on the list. Append it to every newsletter you
send: the link is what makes the list self-service.

## The panel

`\Nino\Modules\Newsletter\Admin` is answered by `adminPanels()`, so it is in
the workbench exactly while the feature is active - after activating or
deactivating, reload the page.

| | |
| --- | --- |
| Navigation | **Newsletter**, uri `newsletter`, position 65. `nav()` names the Content group, but a panel a feature brings lands under Features whatever it names - `\Nino\Admin\Panels` decides that, not the panel |
| Permission | `/_admin/newsletter/manage` on every action. The roles tab of the Users panel offers it under Features, the group the panel is in; the **Editor** role is built from the Content panels alone and does not receive it - grant it there |
| Actions | `newsletter/list` (`apiList()`): every recorded entry, pending and confirmed alike, most recent first, each one **without its token** - the panel never draws it and deletes by address, while `Nino.admin.exportCsv()` writes the union of every row's keys and would have put a live unsubscribe credential into a spreadsheet - and **with a `status`**, `pending` or `subscribed` (an entry from before the double opt-in flow, which has none, reads as `subscribed`), plus `counts` `{ subscribed, pending }` and `unsubscribeUrl`, the absolute address of `/.newsletter/unsubscribe` built like the links in the mails · `newsletter/delete` (`apiDelete()`): one entry by `email`; `404` for an unknown address, `500` when the list could not be locked or written |
| Dashboard | `summary()` gives the Dashboard a tile with the count of **confirmed** addresses, labelled `/_admin/dashboard/label/newsletter` - a pending signup has agreed to nothing yet and is not counted |
| Activity log | `log()` writes `Delete Newsletter Subscriber <email>` for every delete |
| Assets | `assets/admin.js`, `assets/admin.css`, named through `\Nino\Admin\Panels::relative()` so they move with the directory |
| Text | `text/en_US.php` and `text/de_DE.php` - the panel's own words, merged into the workbench's fills while the feature is active |

The script renders the count of confirmed addresses (with the pending ones
named beside it, `12 subscribers · 3 pending`), a read-only textarea with the
**confirmed** addresses as a comma-separated BCC line and a **Copy** button
beside it, a hint under it with the address of `/.newsletter/unsubscribe` to put
into every BCC mail, an **Export as CSV** button (`newsletter.csv`, the name is
the fill `/_admin/newsletter/label/filename`), and the entries as a searchable,
sortable, paged table with a status column and a **Delete** button per row. A
filter above the table - **All** (the default), **Confirmed**, **Pending** -
sets the rows of the table that is there, and the CSV export writes the rows the
filter shows, status column included; the BCC line is the confirmed addresses
whatever the filter says. A delete asks for
confirmation first, then records the address as removed *before* dropping
it from the list: if the removal record cannot be written, the list stays
untouched. Everything the panel shows is fetched again after each change.

## The install unit

`install/manifest.php` is applied by the activation add-only: what the
project already has stays, only what is missing arrives. It carries no
routes (the class registers them), no `config` defaults and no element
types.

**Templates**, copied into the project's `templates/`:

| Template | Purpose |
| --- | --- |
| `page-newsletter.tpl` | the page `/.newsletter` renders for every confirm and unsubscribe outcome, and `/.newsletter/unsubscribe` for its answer: `[[/newsletter/page/title]]`, `[[/newsletter/page/text]]` and a button back to `[[/webpage/home/uri]]`, inside `[template /templates/html-header]` and `html-footer` |
| `page-newsletter-unsubscribe.tpl` | the form `GET /.newsletter/unsubscribe` renders: a `form.nino-form--inline` posting to `[[/nino/dir]]/.newsletter/unsubscribe` with `[csrf]`, the `location` trap, an `email` input, the button `[[/newsletter/unsubscribe/submit]]` and the error line `[[/newsletter/unsubscribe/error]]`. It carries neither `.nino-form` nor `.nino-newsletter-form`: the kernel script binds every one of those to an xhr handler that prevents the native post and shows the contact form's success text |
| `mail-newsletter-confirm.tpl` | the confirmation mail: `[[/mail/newsletter/title]]`, `intro`, the button to `[[/newsletter/confirm/url]]` labelled `[[/mail/newsletter/action]]`, `notice`, `closing` and `[[/company/name]]`, inside the mail frame |
| `mail-newsletter-unsubscribe.tpl` | the mail that carries the unsubscribe link: `[[/mail/newsletter/unsubscribe/title]]`, `intro`, the button to `[[/newsletter/unsubscribe/url]]` labelled `[[/mail/newsletter/unsubscribe/action]]`, `notice`, `closing` and `[[/company/name]]`, inside the mail frame |
| `mail-header.tpl`, `mail-footer.tpl` | the mail frame: a complete html document with inline styles from the `/mail/style/*` fills and the logo from `https://[[/website/url]][[/nino/public]]/images/logo.png`. The Form module's unit ships the same two files; whichever unit is applied first provides them, and the other leaves them alone |

**Text**, merged into the project's `text/` files - a key the project
already has stays:

| File | Keys |
| --- | --- |
| `install/text/global.php` | `/mail/style/color/primary`, `text`, `background`, `border`, `section/alt/bg`; `/mail/style/typography/line-height`, `font-small`, `font-big`; `/mail/style/spacing/1`, `2`, `3` - the values the mail frame's inline styles read |
| `install/text/en_US.php`, `de_DE.php` | `/newsletter/label/email`, `submit`; `/newsletter/info/required`, `email`, `success`, `existing`, `error`; `/mail/newsletter/subject`, `title`, `intro`, `action`, `notice`, `closing`; `/newsletter/page/confirmed/title`, `text`, `/newsletter/page/unsubscribed/title`, `text`, `/newsletter/page/invalid/title`, `text`, `/newsletter/page/unsubscribe-requested/title`, `text`; `/newsletter/unsubscribe/title`, `text`, `submit`; `/mail/newsletter/unsubscribe/subject`, `title`, `intro`, `action`, `notice`, `closing` |

The unit is applied add-only: a project that activated the feature before the
unsubscribe route existed gets the two new templates and the new keys when it
activates the feature again (the Features panel's update), and keeps every file
and key it has. Where a project already carries `page-newsletter.tpl` it keeps
its own - the new page `/.newsletter/unsubscribe` answers with it.

The eleven `/mail/style/*` keys are also listed under `blacklist` and are
merged into `text/blacklist.php`: they are technical values, hidden from the
Text panel's normal editing. So are `/newsletter/confirm/url`,
`/newsletter/unsubscribe/url`, `/newsletter/unsubscribe/error`,
`/newsletter/page/title` and `/newsletter/page/text`, which the class fills
at request time and no text file answers - blacklisted, the Text panel's
scan for missing keys does not report them. `label`, `moduleClass` and `requiresModules` in
the unit's manifest are the setup wizard's keys and are not read by an
activation - the wizard does not offer features.

## Configuration

The feature declares no settings: `'settings' => []` in the manifest, and
the Features panel shows no form for it. Five plain `config.php` keys are
read by the class, each with a default:

| Key | Default | Purpose |
| --- | --- | --- |
| `/nino/newsletter/page-template` | `/templates/page-newsletter` | the template `GET /.newsletter` renders |
| `/nino/newsletter/confirm-template` | `/templates/mail-newsletter-confirm` | the confirmation mail's template |
| `/nino/newsletter/unsubscribe-template` | `/templates/page-newsletter-unsubscribe` | the template `GET /.newsletter/unsubscribe` renders - the form |
| `/nino/newsletter/unsubscribe-mail-template` | `/templates/mail-newsletter-unsubscribe` | the mail that carries the unsubscribe link |
| `/nino/newsletter/pending-days` | `7` | how long an unconfirmed signup is kept |

Point the templates at ones of your own when the copied ones are not what the
site needs; the copies themselves belong to the project after activation
and are never replaced by an update.

Raise the days for an audience that confirms slowly. Under that window sits a
ceiling of 500 unconfirmed entries (`Newsletter::PENDING_LIMIT`), because a
burst arrives faster than a week passes: past it the oldest unconfirmed entry
makes room for the newest. Both rules exist because the signup endpoint is
public and the file is rewritten whole on every post - without them 2000 posts
with distinct addresses stored 404 KB that never expired, and took a signup
from 0.97 ms to 5.87 ms. A confirmed subscriber is never touched by either.

## Data and restore

Two files under `data/`, both listed under `data` in the manifest, so the
workbench's daily backup carries them:

| File | Content |
| --- | --- |
| `/data/newsletter.php` | one growing list, one entry per address: `email`, `token`, `status` (`pending` or `subscribed`), `date`, `ip`. An entry without `status` - written before the double opt-in flow - counts as subscribed |
| `/data/newsletter-removed.php` | a flat, append-only list of the sha256 of every address ever removed, by unsubscribe link or by the panel. The hash, not the address: the list is never pruned by design, and an address must not sit in it past its own deletion. A fresh signup clears its own hash |

`init()` registers `callbackRestore()` under `'/nino/admin/restore'`. The
Backups panel and the recovery page call it with `{ dataDir, staging }` -
the live `data/` directory and the extracted backup - before the extracted
copy is copied over the live one, and the callback rewrites the two staged
files: the removal lists of both sides are unioned, and every staged entry
whose hash is in that union is dropped. An address someone removed stays
removed however old the restored backup is; the merge only ever excludes,
never decides who should be on the list. A backup that carries neither file
is left alone. The callback runs only while the feature is active - a
project that deactivated the feature and restores gets the backup's files
as they are, which nothing reads until the feature returns.

## Tests

`tests/newsletter-smoke.php` is the feature's own test: the manifest and the
activation through `\Nino\Features` with the unit applied, the signup,
confirm and unsubscribe flow with the honeypot, the CSRF guard and the
removal record, what the signup is answered for a refused mail, a capped ip and
a list that cannot be written - the same for a new, a pending and a subscribed
address - the way out without a link and the identical answers it gives, the
panel's two actions with their permission and the status they carry, the panel
leaving the registry on deactivation, the restore merge, and what the
confirmation mail can be replied to - the chained owner fill resolved, and no
Reply-To at all where nothing installed one. It loads Nino's
`tests/harness.php` from the checkout three levels up - where the feature
sits in a project - or from the one `NINO_ROOT` names, and defines
`NINO_FEATURES_DIR` as this feature's parent directory, so the kernel serves
the class from wherever the feature is. `tests/newsletter-js-smoke.js` draws
the panel's list over a dom stand-in (the BCC line, the status, the filter, the
export) and runs on its own with node, and from `newsletter-smoke.php` where node
is on the path:

```bash
php features/Newsletter/tests/newsletter-smoke.php
NINO_ROOT=../nino php features/Newsletter/tests/newsletter-smoke.php
```

With the feature copied into a checkout, Nino's `tests/features-smoke.php`
validates the manifest as well, and PHPStan analyses the class and the panel.

# Newsletter

**Key:** `newsletter` · **Class:** `\Nino\Modules\Newsletter` · **Version:** 1.0.0 · **Nino:** `^1.0`

Double opt-in newsletter signup, everything under the `/.newsletter` uri: a
visitor posts an address, receives a confirmation mail, and is on the list
only once the link in it is visited. The same per-subscriber token drives
the self-service unsubscribe link. The workbench gets a **Newsletter** panel
with the list, a copyable BCC line, a CSV export and a delete. Sending the
newsletter itself is not part of the feature - that happens in a mail client
or a project's own mailing service; the panel gets the addresses there.

One directory, the shape the [feature recipe](https://github.com/dapeio/nino/blob/main/docs/recipes/feature.md)
describes: `feature.php`, `Newsletter.php`, `Admin/Admin.php`, `assets/`,
`install/`, `text/`. The changes per version are in [CHANGELOG.md](CHANGELOG.md).

## Routes

`init()` registers both routes itself, so a project that switches the
feature on has a working flow without adding anything to `config.php`:

| Route | `uri` | Handler |
| --- | --- | --- |
| `POST://.newsletter` | `/.newsletter` | `callbackResponse()` on `/nino/http/response/POST://.newsletter` |
| `GET://.newsletter` | `/.newsletter` | `callbackAction()` on `/nino/http/response/GET://.newsletter`, body `[template /templates/page-newsletter]` |

### `POST /.newsletter` - the signup

Reads `email` and `location` from the posted form. `location` is a honeypot
and has to stay empty. The handler respects a rejection by the kernel's CSRF
check (`./nino/csrf/blocked`), so the form has to render `[csrf]`.

| Answer | When |
| --- | --- |
| `400` | `email` missing, or not a valid address |
| `418` | the honeypot is filled |
| `200`, body `{ "status": "ok" }` | in every other case |

The `200` is deliberately the same whether the address is new, already
pending or already subscribed: the form is public, and a different answer
would tell anyone whether a given address is on the list. Internally the
signup does one of three things, none of which the visitor can tell apart:
an address that is already subscribed gets nothing; a pending one gets its
confirmation mail again with the same token, because the first may never
have arrived; a new one is recorded as pending with a fresh token and gets
the mail. A recorded signup also clears the address from the removal record
(see [Data and restore](#data-and-restore)) - a fresh signup is a current
consent. A storage failure is logged and never turns into a `500`.

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

### The signup form

The feature ships no form. Nino's Templates panel does: the `newsletter-form`
section preset under `_nino/Nino/Modules/Templates/library/newsletter-form/`
renders a `form.nino-newsletter-form` with `action="/.newsletter"`, `[csrf]`,
the `location` trap and the `email` field, and `_nino/Nino.ui.js` submits it
by xhr and shows the outcome. The words that form and script read are what
the install unit writes: `/newsletter/label/email`,
`/newsletter/label/submit`, `/newsletter/info/required`,
`/newsletter/info/email`, `/newsletter/info/success` and
`/newsletter/info/error`. `/newsletter/info/existing` is written as well but
never shown by the shipped handler, because the endpoint does not
distinguish the case. A form of your own posts the same two fields to the
same uri.

### The confirmation mail

`_sendConfirmMail()` renders the template `/nino/newsletter/confirm-template`
names - `/templates/mail-newsletter-confirm` by default - with the fill
`[[/newsletter/confirm/url]]` set to the confirm link, in the visitor's
current locale, and sends it through `\Nino\Mail::send()` to the address,
with `[[/mail/newsletter/subject]]` as the subject and `[[/form/email/owner]]`
as Reply-To. `[[/form/email/owner]]` is the fill the Form module's unit
writes; the newsletter's own unit does not. `Mail::send()` rate-limits per
client IP. A failed mail is not shown to the visitor: the pending entry is
recorded already, and submitting again resends it.

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
| Navigation | **Newsletter** in the Content group (uri `newsletter`, position 65) |
| Permission | `/_admin/newsletter/manage` on every action. A Content permission is offered on the roles tab of the Users panel; the **Editor** role does not receive it by itself - grant it there |
| Actions | `newsletter/list` (`apiList()`): every recorded entry, pending and confirmed alike, most recent first · `newsletter/delete` (`apiDelete()`): one entry by `email`; `404` for an unknown address, `500` when the list could not be locked or written |
| Dashboard | `summary()` gives the Dashboard a tile with the count of entries, labelled `/_admin/dashboard/label/newsletter` |
| Activity log | `log()` writes `Delete Newsletter Subscriber <email>` for every delete |
| Assets | `assets/admin.js`, `assets/admin.css`, named through `\Nino\Admin\Panels::relative()` so they move with the directory |
| Text | `text/en_US.php` and `text/de_DE.php` - the panel's own words, merged into the workbench's fills while the feature is active |

The script renders the count, a read-only textarea with every address as a
comma-separated BCC line and a **Copy** button beside it, an **Export as
CSV** button (`newsletter.csv`, the name is the fill
`/_admin/newsletter/label/filename`), and the entries as a searchable,
sortable, paged table with a **Delete** button per row. A delete asks for
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
| `page-newsletter.tpl` | the page `/.newsletter` renders for every confirm and unsubscribe outcome: `[[/newsletter/page/title]]`, `[[/newsletter/page/text]]` and a button back to `[[/webpage/home/uri]]`, inside `[template /templates/html-header]` and `html-footer` |
| `mail-newsletter-confirm.tpl` | the confirmation mail: `[[/mail/newsletter/title]]`, `intro`, the button to `[[/newsletter/confirm/url]]` labelled `[[/mail/newsletter/action]]`, `notice`, `closing` and `[[/company/name]]`, inside the mail frame |
| `mail-header.tpl`, `mail-footer.tpl` | the mail frame: a complete html document with inline styles from the `/mail/style/*` fills and the logo from `https://[[/website/url]][[/nino/public]]/images/logo.png`. The Form module's unit ships the same two files; whichever unit is applied first provides them, and the other leaves them alone |

**Text**, merged into the project's `text/` files - a key the project
already has stays:

| File | Keys |
| --- | --- |
| `install/text/global.php` | `/mail/style/color/primary`, `text`, `background`, `border`, `section/alt/bg`; `/mail/style/typography/line-height`, `font-small`, `font-big`; `/mail/style/spacing/1`, `2`, `3` - the values the mail frame's inline styles read |
| `install/text/en_US.php`, `de_DE.php` | `/newsletter/label/email`, `submit`; `/newsletter/info/required`, `email`, `success`, `existing`, `error`; `/mail/newsletter/subject`, `title`, `intro`, `action`, `notice`, `closing`; `/newsletter/page/confirmed/title`, `text`, `/newsletter/page/unsubscribed/title`, `text`, `/newsletter/page/invalid/title`, `text` |

The eleven `/mail/style/*` keys are also listed under `blacklist` and are
merged into `text/blacklist.php`: they are technical values, hidden from the
Text panel's normal editing. `label`, `moduleClass` and `requiresModules` in
the unit's manifest are the setup wizard's keys and are not read by an
activation - the wizard does not offer features.

## Configuration

The feature declares no settings: `'settings' => []` in the manifest, and
the Features panel shows no form for it. Two plain `config.php` keys are read
by the class, each with a default:

| Key | Default | Purpose |
| --- | --- | --- |
| `/nino/newsletter/page-template` | `/templates/page-newsletter` | the template `GET /.newsletter` renders |
| `/nino/newsletter/confirm-template` | `/templates/mail-newsletter-confirm` | the confirmation mail's template |

Point them at templates of your own when the copied ones are not what the
site needs; the copies themselves belong to the project after activation
and are never replaced by an update.

## Data and restore

Two files under `data/`, both listed under `data` in the manifest, so the
workbench's daily backup carries them:

| File | Content |
| --- | --- |
| `/data/newsletter.php` | one growing list, one entry per address: `email`, `token`, `status` (`pending` or `subscribed`), `date`, `ip`. An entry without `status` and `token` - written before the double opt-in flow - counts as subscribed |
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
removal record, the panel's two actions with their permission, the panel
leaving the registry on deactivation, and the restore merge. It loads Nino's
`tests/harness.php` from the checkout three levels up - where the feature
sits in a project - or from the one `NINO_ROOT` names, and defines
`NINO_FEATURES_DIR` as this feature's parent directory, so the kernel serves
the class from wherever the feature is:

```bash
php features/Newsletter/tests/newsletter-smoke.php
NINO_ROOT=../nino php features/Newsletter/tests/newsletter-smoke.php
```

With the feature copied into a checkout, Nino's `tests/features-smoke.php`
validates the manifest as well, and PHPStan analyses the class and the panel.

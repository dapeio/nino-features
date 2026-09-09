# Forms

**Key:** `forms` · **Class:** `\Nino\Modules\Forms` · **Version:** 1.0.0 · **Nino:** `^1.1`

Any number of forms, each with fields of its own, all behind the one endpoint
`POST /.form` that Nino's contact form has always used. A form is defined in
the workbench, rendered on any page with `[form key="…"]`, mailed to whoever
the form names, and recorded so the **Forms** panel can show, search, export
and delete what came in. The mail goes out through `\Nino\Mail::send()`, so a
project running the Mailer feature sends it over SMTP without this feature
knowing.

It **replaces** the kernel's own contact form (`\Nino\Modules\Form`) rather
than sitting beside it - see [Replacing the kernel's contact form](#replacing-the-kernels-contact-form).

One directory, the shape the [feature recipe](https://github.com/dapeio/nino/blob/main/docs/recipes/feature.md)
describes: `feature.php`, `Forms.php`, `Admin/Admin.php`, `assets/`,
`install/`, `text/`, `tests/`. The changes per version are in
[CHANGELOG.md](CHANGELOG.md).

## Replacing the kernel's contact form

Nino ships a contact form of its own as the kernel module `\Nino\Modules\Form`,
and the wizard's contact page posts to `/.form`. This feature answers that same
endpoint on purpose: a page written against the kernel's form keeps working
unchanged, and the form it posts to is simply the first one defined here.

Both answering it would mean two mails and two records for one visitor, so
**this feature stands down entirely while the kernel module is still listed in
`/nino/modules`**: `init()` registers no route, no shortcode and no callback,
and the panel says why in one line at the top of its list. Remove
`'\\Nino\\Modules\\Form'` from `/nino/modules` in `config.php` and the feature
takes over.

Nothing is migrated: this is a first version, and the kernel form's own
`/data/forms.<Y-m>.php` files are left where they are until their retention
window removes them.

## The endpoint

`init()` registers the route itself, so a project that switches the feature on
has a working endpoint without adding anything to `config.php`:

```
POST /.form
```

Every form posts here. Which form a submission belongs to is the hidden `form`
field that `[form]` renders; **a submission carrying none belongs to the first
form defined**, which is what a hand-written contact page posts.

The answers are the ones the shared `.nino-form` script already knows (see
`_nino/Nino.ui.js` in Nino):

| Status | When |
| --- | --- |
| 200 | accepted - the body is `{ "status": "ok" }` |
| 400 | a required field is empty, or a value is not of the shape its field declares - the one refusal a visitor can act on |
| 404 | the posted `form` key belongs to no form, ie. a page pointing at a form that was renamed |
| 418 | the honeypot was filled, a blocked word was carried, or the form came back faster than a person could fill it |
| 429 | this ip has spent its hour's allowance |
| 403 | the csrf token was missing or wrong - the kernel's own `Csrf` callback, before this feature runs |

Every spam refusal answers 418, and the script shows one generic message for
anything that is not 200 or 400: a bot must not learn which check it tripped.

## `[form]`

```
[form]                 the first form defined
[form key="quote"]     the form with that key
```

Renders the markup the shared `.nino-form` script drives: the csrf token, the
hidden key, the moment the form was drawn, one control per declared field, the
honeypot, the live region a message is written into, and the submit button. A
key no form has renders nothing at all.

A field's **label** is rendered through `\Nino\Html::renderHtml()`, so a text
fill such as `[[/form/label/name]]` written into the label field of the panel
resolves per locale - one definition serves every language the site speaks.

## The panel

**Forms**, permission `/_admin/forms/manage`. It sits in the rail's own
**Features** group: a panel a feature brings always does, whatever its `nav()`
names. Three levels:

1. **The forms** - one row each, with how many submissions it holds and the
   shortcode that renders it.
2. **One form** - its name, key, recipient and subject, whether the visitor
   gets a confirmation mail, the two mail templates it renders, and its fields:
   name, label, type, required, and the options of a `select`.
3. **Its submissions** - the shared sortable, searchable, paged table, a CSV
   export of exactly the rows it holds, and a delete per row.

Actions: `forms/list`, `forms/save`, `forms/delete`, `forms/entries`,
`forms/entry-delete`. Every one is guarded with
`\Nino\Admin\Admin::guardPerm()`.

A form's **name** is what the panel shows; its **key** is what the markup posts
and what its submissions are filed under. Renaming a key keeps one definition
rather than making a second, and its submissions stay filed under the old key -
they are what someone sent, not part of the definition.

Deleting a form leaves its submissions on disk too; the retention window
removes them on its own schedule.

### The field types

`text`, `email`, `tel`, `url`, `number`, `textarea`, `select`.

**No checkbox yet.** The shared `.nino-form` script every Nino form is driven by
posts each field's `.value` unconditionally (see `_nino/Nino.ui.js`), and an
unticked checkbox's value is still the string `"on"` - a box nobody ticked would
be mailed and recorded as ticked. That is a gap in the kernel's own script, and
a feature may not paper over it with a script of its own; the type is added here
as soon as Nino sends a checkbox's checked state.

A field's **name** is what it is posted and exported as, so it is an identifier
(`^[a-zA-Z][a-zA-Z0-9_-]*$`), never `form`, `location`, `_csrf` or `_t` - the
four names the endpoint owns. A duplicate name, an unusable one, or a type the
feature does not know is dropped when the definition is saved; a form left with
no usable field at all is refused.

## Spam protection

Four guards, none of them a third party and none of them a captcha:

| Guard | What it does |
| --- | --- |
| Honeypot | `[form]` renders a `location` field no person sees. Filled → 418. |
| Speed trap | `[form]` stamps the moment it was drawn into `_t`. A submission that comes back faster than **Fastest accepted submission** seconds → 418. Only a form carrying the stamp is judged by it, so a hand-written form is never refused for it - and a bot that omits the field simply faces the other three. |
| Blocked words | **Blocked words**, one per line, matched case-insensitively as a substring against every submitted value → 418. |
| Rate limit | **Submissions per hour and address**: how often one ip may submit before it is turned away → 429. The ip is stored as a sha256, not as itself; the counter is `data/forms/rate.php`. |

The kernel's own per-ip mail cap applies underneath all of this
(`\Nino\Mail::send()`), and a submission whose mail that cap refused is not
recorded - one entry per request regardless would turn a throttled flood into
unthrottled disk growth from an unauthenticated endpoint.

## The mail

Two mails per accepted submission:

- the **owner notification**, to the form's recipient - or, where the form
  names none, to the address in the text fill `/form/email/owner`. Its
  `Reply-To` is the first email field the submission carried, so a reply
  reaches the visitor. Always rendered in the site's **native** locale.
- the **confirmation**, to that same address, where the form asks for one.
  Rendered in the locale the visitor filled the form in.

Both are templates, `/templates/mail-form-owner` and `/templates/mail-form-user`
by default, and a form may name others. The template is rendered first and the
placeholders replaced in the result, so a submitted value can never be read as a
fill, a shortcode or a template include:

| Placeholder | What it becomes |
| --- | --- |
| `[[fields]]` | the whole submission as a `<table>` of label/value rows - what a form with fields nobody knew in advance needs |
| `[[form]]` | the form's name |
| `[[date]]` | when it arrived |
| `[[name]]`, `[[email]]`, `[[message]]`, `[[subject]]` | filled where the form has a field of that name, so a project's own mail template from before this feature keeps rendering |

## The install unit

Applied when the feature is activated, add-only - anything the project already
has is left as it is:

- `templates/mail-form-owner.tpl` and `templates/mail-form-user.tpl`, the two
  mail bodies. Names of their own, so they can never displace the
  `mail-owner.tpl`/`mail-user.tpl` a project wrote against the kernel's contact
  form - a form may point at those instead.
- `templates/mail-header.tpl` and `templates/mail-footer.tpl`, the frame both
  include, where the project has none.
- the `/form/...` and `/mail/...` fills both mails and the built-in form read,
  per available locale, and `/form/email/owner` in `text/global.php`.

## Settings

In the Features panel, stored under `/nino/features/forms/settings` in
`config.php`:

| Setting | Default | What it does |
| --- | --- | --- |
| Keep submissions for | 3 months | How many months stay on disk. An older month is deleted the next time a submission comes in. |
| Submissions per hour and address | 10 | The rate limit above. 0 switches it off. |
| Fastest accepted submission | 3 s | The speed trap above. 0 switches it off. |
| Blocked words | – | One per line. |
| Record submissions | on | Off means the mail goes out and nothing is written; the panel then stays empty by design. |

## Data and restore

Everything the feature owns is `data/forms/`, which is what the manifest
declares and what a backup carries:

| File | What it holds |
| --- | --- |
| `definitions.php` | the forms. Absent until the panel saves once - until then the built-in contact form is what is offered, and nothing has been written. |
| `<key>.<Y-m>.php` | one form's submissions for one month: `id`, `date`, `form`, `ip`, `fields`. Pruned to the retention window. |
| `rate.php` | the rate counter, hashed ips and their reset times. |

Values are stored html-escaped, the way the kernel's contact form stored them,
and the panel decodes them again on render (`Nino.admin.decodeEntities()` into
`textContent`, never into markup).

A restore **merges** rather than overwrites: the restored month and the live one
are joined and deduplicated, so a submission that arrived after the backup was
taken is not lost - it is an inquiry nobody else has a copy of. The definitions
are the backup's, which is what restoring them means. Registered as a
`'/nino/admin/restore'` callback in `init()`, so a project without this feature
has no callback and nothing is autoloaded on its behalf.

## Tests

```bash
NINO_ROOT=../nino php features/Forms/tests/forms-smoke.php
```

`tests/forms-smoke.php` covers the manifest and the activation, the stand-down
while the kernel's contact form is on, what `normalize()` makes of a definition,
the `[form]` markup, every refusal and every guard of the endpoint, the two
mails handed to the transport, what is recorded and pruned, all five panel
actions with their permission, and the restore merge.

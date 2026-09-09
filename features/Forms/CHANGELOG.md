# Changelog

All notable changes to the Forms feature are documented in this file.
A release is the tag `forms-<version>` of dapeio/nino-features.

## 1.0.0 — 2026-09-08

First release.

- Any number of forms, defined in the workbench, each with fields of its own:
  `text`, `email`, `tel`, `url`, `number`, `textarea`, `select`. No checkbox
  yet - see the note in `README.md`, it waits on Nino's own form script.
- One endpoint, `POST /.form` - the one Nino's own contact form has always
  used, so a page written against that keeps working. The feature stands down
  entirely while `\Nino\Modules\Form` is still listed in `/nino/modules`.
- `[form]` renders a form from its definition, with the csrf token, the hidden
  key, the honeypot and the speed trap's stamp.
- Spam protection without a third party: honeypot, speed trap, a blocked-word
  list, and a per-ip hourly rate limit whose counter stores a hash, not an ip.
- Mail through `\Nino\Mail::send()`, so the Mailer feature can carry it: the
  owner notification in the site's native locale, the visitor's confirmation in
  theirs. `[[fields]]` in a mail template is the whole submission as a table.
- A **Forms** panel (`/_admin/forms/manage`), in the rail's Features group
  like every panel a feature brings: the forms,
  one form's fields, and one form's submissions as the shared sortable,
  searchable table with a CSV export and a delete per row.
- Submissions under `data/forms/`, one file per form and month, pruned to the
  retention setting. A restore merges the backup's month with the live one
  instead of overwriting it.

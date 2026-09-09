# Forms

**Key:** `forms` · **Class:** `\Nino\Modules\Forms` · **Version:** 1.0.0 · **Nino:** `^1.1`

A builder for Nino's own form endpoint. Nino has always had one form: a
contact form, defined in the kernel, posted to `POST /.form`. Since 1.1 it can
have any number, defined under `/nino/form/forms` in `config.php` - and this
feature is what edits them, draws them and keeps the spam out.

It **replaces nothing.** `\Nino\Form` stays the engine - which forms there
are, what a submission has to look like, the mail pair it sends, the record it
leaves - and `\Nino\Modules\Form` keeps the route. Switching this feature on
adds three things and changes nothing else; switching it off leaves every form
a project defined working, because the definitions were never this feature's
to begin with.

| What it adds | Where it sits |
| --- | --- |
| `[form key="…"]` | a shortcode that draws a form from its definition |
| the **Forms** panel | the builder for `/nino/form/forms`, plus how long submissions are kept |
| three spam guards | on the kernel's own route callback, ahead of the engine |

The submissions themselves are **not** here: they are the kernel's, and the
workbench's own **Submissions** panel shows, filters, exports and deletes
them - for every form, whether this feature is installed or not.

One directory, the shape the [feature recipe](https://github.com/dapeio/nino/blob/main/docs/recipes/feature.md)
describes: `feature.php`, `Forms.php`, `Admin/Admin.php`, `assets/`, `text/`,
`tests/`. No `install/` unit: a form points at the mail templates the kernel's
contact form already installed. The changes per version are in
[CHANGELOG.md](CHANGELOG.md).

## What a form is

A definition, in `config.php`, exactly as the [Forms section](https://github.com/dapeio/nino/blob/main/docs/development.md#forms)
of the developer manual describes it:

```php
'/nino/form/forms' => [
	[
		'key'						=> 'quote',
		'name'					=> 'Quote request',
		'to'						=> 'sales@example.com',	// '' sends to '[[/form/email/owner]]'
		'subject'				=> '',									// '' uses '[[/form/subject/owner]]'
		'confirm'				=> true,								// a confirmation to the first address given
		'ownerTemplate'	=> '/templates/mail-owner',
		'userTemplate'	=> '/templates/mail-user',
		'fields'				=> [
			[ 'name' => 'email',	'label' => '[[/form/label/email]]', 'type' => 'email', 'required' => true ],
			[ 'name' => 'budget',	'label' => 'Budget',								'type' => 'number' ],
		],
	],
],
```

The panel writes this file and nothing else. A definition written by hand
shows up in the panel; one saved in the panel is read by the engine on the
next request. There is no second copy anywhere, which is why switching the
feature off costs a project nothing.

Validation is `\Nino\Form::normalize()` - the engine's own, not a copy of it.
What the panel accepts is exactly what the endpoint accepts.

## The shortcode

```
[form]                  the first form defined - what a page posting no key belongs to
[form key="quote"]      a particular one
```

It renders the markup the shared `.nino-form` script drives: the csrf token,
one control per field with its label resolved (a label written as a textfill
is resolved before it is shown, so one label serves every language), the
honeypot, the live region the script writes its answer into, and the submit
button. Plus two hidden fields - the form's key, and the moment the form was
drawn, which is what the "fastest accepted submission" guard reads.

A key no form has renders nothing at all: an empty page beats a form that
posts nowhere.

The markup a project already has keeps working. `page-contact.tpl` from the
wizard is hand-written and carries no key, so it posts to the first form
defined - which is the contact form until somebody reorders the list.

## The panel

**Forms**, in the Features group, behind `/_admin/forms/manage`.

The list is one card per form: its name, the shortcode that draws it, its
field names and how many submissions it has on file. A card leads to that
form's own screen - what it is called, where its mail goes, which templates it
renders, and its fields as one row each.

Under the list sit the two things about the submissions a project decides,
because the kernel is what writes them and a project keeps them when this
feature goes:

| Control | Config key |
| --- | --- |
| **Keep for (months)** | `/nino/form/retention`, 1 to 60 |
| **Record submissions** | `/nino/form/store` - off means the mail goes out and nothing is written |

Deleting a form leaves its submissions alone: they are what a person asked
for, not a property of the definition, and the Submissions panel goes on
showing them under the key they were recorded with. The last form cannot be
deleted - a project with none falls back to the built-in contact form, and
having no form at all is done by switching the `Form` module off.

## The guards

All three sit on `/nino/http/response/POST://.form` at priority 1 - the
kernel's own route callback, ahead of the engine. A guard that refuses leaves
a status behind and `\Nino\Form::handle()` returns without sending or writing
anything. This is the seam `\Nino\Csrf::init()` already uses; it needs no
callback name of its own.

| Setting | What it does |
| --- | --- |
| **Fastest accepted submission** | a submission that comes back sooner than this after the form was drawn is a script. Only forms drawn by `[form]` carry the stamp - a hand-written one is never checked. `0` off |
| **Blocked words** | one per line, case ignored, matched as a substring anywhere in any value |
| **Submissions per hour and address** | how many *accepted* submissions one ip may make before the next is turned away. `0` off |

The first two answer **418**, the same as a filled honeypot: the shared script
shows one generic message for anything that is not 200 or 400, so a bot never
learns which check it tripped. The rate limit answers **429** - it is the only
refusal a person can meet by using the site normally, and the only one they
can do something about.

The rate limit is counted at priority 8, after the engine answered 200, so
only a submission that was actually sent costs a slot: a visitor who mistypes
their address four times has not submitted four times. The counter lives in
`/data/forms-rate.php`, keyed by a sha256 of the client address - a spam
counter, not a visitor log - and every entry whose hour has passed is dropped
on the next write.

Nino's own per-ip mail cap (`\Nino\Mail`, 5 per hour) applies on top of all of
this and is the hard stop against the endpoint being used as a relay.

## Data

None. The definitions are configuration, the submissions are the kernel's, and
`/data/forms-rate.php` is a counter that rebuilds itself - which is why the
manifest declares `'data' => []` and a backup carries nothing of this
feature's.

## Tests

```bash
NINO_ROOT=../nino php features/Forms/tests/forms-smoke.php
```

The manifest and the activation, the shortcode over a definition the kernel
reads, the builder writing `config.php`, each guard refusing and each one
letting a good submission through - and the two that matter most for a feature
shaped like this one: that with nothing configured a submission goes through
the engine exactly as it did before, and that after deactivation the forms are
still there and still work.

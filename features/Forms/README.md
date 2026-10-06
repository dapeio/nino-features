# Forms

**Key:** `forms` · **Class:** `\Nino\Modules\Forms` · **Version:** 1.0.0 · **Nino:** `^1.4`

A builder for Nino's own form endpoint. Nino has always had one form: a
contact form, defined in the kernel, posted to `POST /.form`. Since 1.2 it can
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
describes: `feature.php`, `Forms.php`, `Admin/Admin.php`, `assets/`
(`admin.js` and `admin.css` for the panel, `forms.css` for the two fields that
need one on the page), `templates/` (the markup of the form and of one field
each), `text/`, `tests/`. No `install/` unit: a form points at the mail
templates the kernel's contact form already installed. The changes per version are in
[CHANGELOG.md](CHANGELOG.md).

It needs Nino 1.4: the three field types below and the engine's own
`\Nino\Form::problems()`, which the panel asks what to refuse, arrived there.

## What a form is

A definition, in `config.php`, exactly as the [Forms section](https://github.com/dapeio/nino/blob/main/docs/development.md#forms)
of the developer manual describes it:

```php
'/nino/form/forms' => [
	[
		'key'						=> 'quote',
		'name'					=> 'Quote request',
		'to'						=> 'sales@example.com',	// '' sends to '[[/project/mail/address/owner]]'
		'subject'				=> '',									// '' uses '[[/module/form/subject/owner]]'
		'confirm'				=> true,								// a confirmation to the first address given
		'ownerTemplate'	=> '/templates/mail-owner',
		'userTemplate'	=> '/templates/mail-user',
		'fields'				=> [
			[ 'name' => 'email',	'label' => '[[/template/common/form/email]]', 'type' => 'email', 'required' => true ],
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

The field types, with what each one posts:

| Type | Draws | Posts |
| --- | --- | --- |
| `text`, `email`, `tel`, `url`, `number` | an input of that type | what was typed, checked for its shape |
| `textarea` | a text area | what was typed |
| `select` | a drop-down list of `options` | one of the options |
| `radio` | a group of radio buttons, one per option - **needs at least one option** | the ticked option |
| `checkbox` | one checkbox with its label | `1` when ticked, nothing when not - a required one has to be ticked |
| `date` | the browser's date input | `Y-m-d`, a day that exists |

An option may be written as a text fill, like a label (`[[/template/page-contact/option/small]]`):
it shows the text it stands for and posts the key, which is what is stored and
what the engine compares it with. The mail's `[[fields]]` and the Submissions
panel show that stored key, not the words it stands for.

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

A checkbox is one `<label>` around the input and its words, a radio group a
`<fieldset>` with the question as its `<legend>` and one `<label>` per option.
Both carry feature-owned classes - `nino-forms-check` and `nino-forms-group` -
which `assets/forms.css` styles and which the project restyles through the
`--nino-forms-gap` custom property or a rule of its own. The other fields keep
the kernel's `nino-form-input` and `nino-form-textarea`.

**A consent** is a checkbox that is required. The label is plain text - markup
in it is shown as text, not drawn - so a link to the privacy page goes beside
the form, in the template or the text that holds `[form]`:

```
<p>Details are in our <a href="/privacy">privacy policy</a>.</p>
[form key="consent"]
```

The markup a project already has keeps working. `page-contact.tpl` from the
wizard is hand-written and carries no key, so it posts to the first form
defined - which is the contact form until somebody reorders the list.

## The panel

**Forms**, in the Features group, behind `/_admin/forms/manage`.

The list is one card per form: its name, the shortcode that draws it, its
field names and how many submissions it has on file. A card leads to that
form's own screen - what it is called, where its mail goes, which templates it
renders, and its fields as one row each.

In a field's row:

- **Name from the label.** A field that is added here takes its name from its
  label as long as the name is not typed into by hand: `Ihre Straße` becomes
  `ihre-strasse` (umlauts and the sharp s are spelled out, other accents
  dropped, words joined by a hyphen, a letter first, 64 characters at most,
  `date` or another name the form keeps becomes `date-field`, a name another
  field has gets `-2`). A label written as a text fill gives the last part of
  the key: `[[/template/common/form/email]]` becomes `email`. A field that was saved is
  never renamed, whatever its label is changed to. The mail templates
  installed before Nino 1.4 fill only `[[name]]`, `[[email]]`, `[[subject]]` and
  `[[message]]`, so a form that keeps those names for those fields keeps its
  confirmation mail - the first field of a new form is called `name` for that
  reason.
- **Type** in the words of the workbench's language, **Required**, and for a
  select or a radio group the **options**, one per line.
- **Up and down** move the field in the list; the first cannot go up, the last
  cannot go down, and the focus stays on the button that was pressed - at
  either end of the list, where that button is off, it goes to the other one.

The two mail templates are **chosen from the project's own**
`/templates/mail-*.tpl` (without `mail-header` and `mail-footer`). A template
the form names that is not in the list is still shown, so opening a form never
changes it by itself - and saving it is refused: a template that is not on disk
renders as nothing, and the mail that goes out would be an empty one.

A field reaches a mail only where its template shows it. The `mail-owner` and
`mail-user` templates of a new project carry `[[fields]]`, which draws every
field of the form - a checkbox, a radio group, a date and every field of your
own - as a table; a project that was installed before that copies it into its
own templates by hand. A template that fills only `[[name]]` and its three
siblings mails nothing of a field of another name.

**A refused save says where.** What the engine would only repair - a name that
is no identifier or one the form keeps, a name twice, a type nobody knows, a
radio group without options, an address that is none, a template path that is
none or not on disk, a form without a field - is refused instead, with a
sentence under the control it is about, `aria-invalid` on it and the focus on
the first. The sentence for the whole form is also at the top of the editor,
because the status line is not shown on a phone. Nothing is written.

Under the list sits a card of its own, with its own Save, for the two things
about the submissions a project decides, because the kernel is what writes
them and a project keeps them when this feature goes:

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

Both caps count the address the kernel resolved, which behind a reverse proxy
is the proxy for every visitor alike unless it is named under
`/nino/http/proxies` - there, without that key, the first visitor to spend
either allowance turns the site's form off for everybody. See the Config panel's
**Reverse proxies in front of this site**.

## Data

None. The definitions are configuration, the submissions are the kernel's, and
`/data/forms-rate.php` is a counter that rebuilds itself - which is why the
manifest declares `'data' => []` and a backup carries nothing of this
feature's.

## Tests

```bash
NINO_ROOT=../nino php features/Forms/tests/forms-smoke.php
node features/Forms/tests/forms-js-smoke.js
```

The manifest and the activation, the shortcode over a definition the kernel
reads, the builder writing `config.php`, each guard refusing and each one
letting a good submission through - and the two that matter most for a feature
shaped like this one: that with nothing configured a submission goes through
the engine exactly as it did before, and that after deactivation the forms are
still there and still work.

On a Nino older than 1.4 `forms-smoke.php` says so and stops - there is no
`\Nino\Form::problems()` to test against, and the manifest does not offer the
feature to it.

The shortcode over the three new types (the checkbox inside its label, the
radio group as a fieldset, a date as an input), hostile labels and options
drawn as text, an option written as a fill keeping its key as the value and
being accepted when it is posted, and no `<p>` or `<button>` in a field
template - the `.nino-form` script takes the first of each for its own. The
panel refusing a definition at the field, the control or the form, with
nothing written, and a template that is not on disk; the list of templates;
what a checkbox, a radio group and a date post through the endpoint. The two
text files carry the same fills, a name for every field type and a sentence
for every refusal.

`tests/forms-js-smoke.js` is the panel's own script over a dom stand-in. It
checks one fixed action bar on the list (New form) and one in the editor (its
Save), and the two submission settings as a card with their own Save and status
line: what they post, the button kept off while the request runs, a refusal
marked as an error. It also checks that the panel is drawn again when the shell
reopens it. Of the editor it checks the name a label gives (`Straße` becomes
`strasse`, `[[/template/common/form/email]]` becomes `email`, a name that is taken or
reserved, 64 characters at most) and that a name follows its label until it is
typed into, through add, retype and move, while a saved field is never
renamed; the buttons that move a field, the ends disabled and the focus kept;
the type names in the workbench's language, the options box of a radio group,
the template lists that keep what the form names, and where a refused save is
marked. `forms-smoke.php` runs it too where node is on the path.

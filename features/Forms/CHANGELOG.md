# Changelog

All notable changes to the Forms feature are documented in this file. The
format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), the
versions [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## Unreleased

- **A checkbox for a consent, radio buttons and a date.** Three field types
  more, drawn by `[form]` and accepted by the endpoint: a `checkbox` (it posts
  `1` when it is ticked, and a required one has to be), a `radio` group (it
  needs at least one option and posts the ticked one) and a `date` (`Y-m-d`, a
  day that exists). The checkbox and the radio group carry classes of their own,
  `nino-forms-check` and `nino-forms-group`, with a small stylesheet
  (`assets/forms.css`) bundled into the project's `/.cache/style.css`. Every
  field template is one line without a `<p>` or a `<button>`, which the
  `.nino-form` script would take for its message and its submit button. Needs
  Nino `^1.4`, where the three types and `\Nino\Form::problems()` arrived: the
  constraint said `^1.3`, and a Nino older than 1.4 is not offered the feature.

- **A field's name follows its label.** A field added in the panel takes its
  name from its label until the name is typed into by hand - `Ihre Straße`
  gives `ihre-strasse`, `[[/form/label/email]]` gives `email`, a name the form
  keeps becomes `date-field`, one that is taken `-2` - and a field that was
  saved is never renamed. A new field no longer starts without a name.

- **A refused save says where it went wrong.** The panel asks the engine what
  it would leave out or repair (`\Nino\Form::problems()`) and refuses that
  instead of saving something other than what was typed: a name that is none,
  one the form keeps or one that is taken, a radio group without options, an
  unknown type, an address that is none, a template path that is none, a form
  without a field. The sentence stands under the control it is about, the
  control is `aria-invalid` and focused, and the same sentence is at the top of
  the editor, since the status line is not shown on a phone. Nothing is written.

- **The type names are words, and fields can be sorted.** The type list is in
  the workbench's language instead of `textarea`, and every field has a button
  up and a button down; the focus stays on the one that was pressed, or goes to
  the other one at either end of the list, where that one is off.

- **The mail templates are chosen, not typed.** Both are lists of the
  project's own `/templates/mail-*.tpl` (without the header and the footer). A
  template the form names that is not on disk is still shown, so opening a form
  never changes it - and saving it is refused: a missing template renders as
  nothing, and the mail that went out was an empty one. The panel's hint on the
  templates said both of them carry the whole submission, which was not true of
  the ones an installation started from; it now says which placeholder does
  (`[[fields]]`, carried by the templates of a new project, which a project
  installed earlier adds by hand), and the editor gives every control the
  workbench's input class.

- **The panel uses the workbench's request helper, its status line, its wording
  of a failure and its question about unsaved input, where the Nino has them.**
  Every request goes through `Nino.adminUi.api`, which signs the page in again
  over what is on screen when the session has ended instead of losing it. The
  two submission settings and the form editor say "saving", "saved at 09:41" or
  "unsaved changes" in the workbench's status line, and a failed save reads the
  way the workbench says it. The editor is registered with the shell, so its
  back link, a log out or a language change asks **Save**, **Discard** or
  **Cancel** first; and opening the panel again no longer draws the editor over
  what was typed into it. On a Nino without the helper (1.3.x) the panel posts
  as it did, but from the project's own directory: it posted to `/_admin/` from
  the root of the domain, which a project in a subdirectory does not answer.
  With `nino` at `^1.4` (see above) the fallbacks are no longer reached.

- **New form lay under a second Save bar.** The two submission settings drew
  their own fixed action bar over the list's, so on every screen size a click
  on New form hit the Save of the retention instead and saved that. The
  settings are a card of their own now, with their own Save, which stays off
  while the request runs, and a status line that marks a failed save as an
  error; the list carries the one fixed bar it should, and the suite draws
  the panel over a dom stand-in (`tests/forms-js-smoke.js`) to count the bars
  on each screen.

- **Returning to the panel threw instead of drawing the list.** `showCurrent()`,
  which the shell calls whenever the panel is opened again, called
  `_showList()` - a method that has been `_renderList()` since the list and
  the form became two levels - so every second visit to Forms ended in a
  TypeError and an empty screen until the page was reloaded. The suite now
  reads the script and holds every method it calls on its own namespace to
  one it defines.

- Needs Nino `^1.3`, where the constraint said `^1.1`. The sectioned `manual`
  map this manifest carries is only read by a kernel newer than the
  `v1.2.0-beta` tag - `Features::manifest()` refuses it on the tagged one -
  and `^1.1` is satisfied by that kernel, so the catalogue offered the
  feature to an installation that could not then install it. `^1.3` names
  only a kernel that can read the manifest.

### Added

- **It brings its section of the privacy policy.** The feature has an install
  unit now, which carries nothing but the section `forms` (what is stored of a
  submission, the three months it is kept by default, the hash of the address in
  the spam counter) for the type `privacy` of Nino 1.4's Legal module -
  add-only, and a Nino without the module ignores the file. The section names
  three months, the default of `/nino/form/retention`: whoever changes the
  retention changes the section too. It is a starting point, not legal advice -
  see "Legal" in Nino's `docs/development.md`.

### Changed

- **The guards' seam is named for what it is.** The README and the class
  docblock called the `.form` route callback at priority 1 the seam
  `\Nino\Csrf::init()` uses; Csrf refuses on the global
  `/nino/http/response`, one step earlier. The seam is the one Nino's
  manual documents for refusing a submission. Nothing else changed.

- **The list has no empty state, and the comments no longer describe a
  feature that replaces the endpoint.** `\Nino\Form::forms()` answers the
  kernel's contact form while a project has defined none, so the list is never
  empty: the branch and its text `/_admin/forms/hint/empty` are gone. The
  script named a `Forms::TYPES` that is `\Nino\Form::TYPES`, and the list's
  and the shortcode's docblocks described states that no longer exist; the
  `[csrf]` comment rested on a shortcode's output not being rendered again,
  which it is, and `_tooFast()` called the check off by default, where the
  default is three seconds. Words only, apart from the unreachable branch.

- **The words the builder reads follow Nino 1.4's text keys.** A generated
  form's legend and button read `[[/template/common/form/required]]` and
  `[[/template/common/form/submit]]` (`[[/form/required]]` and
  `[[/form/label/submit]]` before), a field's label the same family -
  `[[/template/common/form/email]]` gives the name `email` - and the panel's
  hints name `/project/mail/address/owner` and `/module/form/subject/owner` for
  `/form/email/owner` and `/form/subject/owner`. `nino` was `^1.4` already; the
  keys are why. Nothing has been published under the old keys, so there is no
  migration; a project that already has texts under them copies the values to
  the new keys.

- **Four sentences named something the code does not.** The README dated the
  form engine to Nino 1.1, where `/nino/form/forms` arrived with 1.2.0-beta,
  and listed the directory's contents without the `templates/` the form's
  markup lives in now. In the class, the docblock still said the guards answer
  418 "for all of them" although the rate limit has answered 429 since the
  feature stopped replacing the endpoint, `_blocked()` counted four reserved
  field names where `\Nino\Form::RESERVED` holds seven, and the comment on
  `ROUTE` had ended up above `TEMPLATES` when that constant was added. The
  reverse-proxy note also had "one visitor's four submissions" turning the
  form off for everybody, a number neither cap carries - this feature's
  allowance is ten a hour by default and the kernel's mail cap is five - so it
  names the allowance rather than a number now. Words only - the code is
  unchanged.

### Fixed

- **An option written as a text fill could never be submitted.** `[form]`'s
  output is rendered once more, so a select option such as `[[/shop/small]]` was
  drawn with the text it stands for as its value, and the engine - which
  compares what is posted with the key that is stored - refused it. The bracket
  of a value is a character reference now, which the browser reads back as the
  bracket and the second pass leaves alone; the option's words are still the
  text the key stands for. A radio group takes its options the same way.

- **An invalid byte in a value rendered as nothing.** `htmlspecialchars()`
  answers input that is not valid UTF-8 with `''` unless `ENT_SUBSTITUTE` is
  among its flags, and every call here spelled the flags out without it.
  Every call carries it now, as the kernel's do, and
  `tests/escaping-smoke.php` reads every feature for the next one.

- **A field label was drawn as markup while a select option was drawn as
  text.** Both are the same kind of value - a fill key, or a word an operator
  typed into the Forms panel - and both are rendered so a form can be
  localised. The option escaped what came out of that, the label did not, so
  one form treated one kind of value two ways. Whichever of the two is wrong
  it is the label: the `<label>` is the template's markup, and a form
  definition is not where more of it comes from.

- **Behind a reverse proxy the rate limit counted every visitor as one.**
  Both halves of the per-address cap - the counter written after an accepted
  submission and the check in front of the next one - asked
  `\Nino\Http::getClientIp()` without the app data it needs to resolve an
  address, so behind a proxy they shared one bucket and four submissions by
  anyone turned the form off for everybody. Both pass `$appData` now, so a
  project that named its proxies under `/nino/http/proxies` counts the
  visitor behind them. Needs the kernel change that added the key.

## 1.0.0 — 2026-09-08

First release.

- `[form]` and `[form key="…"]`, drawing a form from its definition under
  `/nino/form/forms` - the markup the shared `.nino-form` script drives, with
  labels resolved through the text system.
- The **Forms** panel: the forms of `config.php` as a list, one form's fields
  on a screen of its own, and the two keys that decide how long submissions
  are kept and whether they are kept at all.
- Three spam guards on the kernel's own route callback at priority 1: the
  moment a form was drawn, a blocklist over every value, and an hourly
  allowance per client address that only accepted submissions spend.

It extends Nino's form endpoint rather than replacing it: `\Nino\Form` stays
the engine and `\Nino\Modules\Form` keeps the route, so a project that
switches this feature off keeps every form it defined, every submission it
recorded, and its Submissions panel.

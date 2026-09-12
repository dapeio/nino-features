# Hello World

A complete feature that does one small thing, written to be copied.

Everything the contract offers appears here exactly once — a shortcode, a route,
a panel, a setting, an install unit, text fills, an asset, stored data, an
upgrade hook and a test. So the directory is also a checklist: start from it,
delete what you do not need, and what is left is still a working feature.

The companion is Nino's own [feature recipe](https://github.com/dapeio/nino/blob/main/docs/recipes/feature.md),
which walks the same ground in prose. Where the two disagree, the recipe is the
contract and this is one reading of it.

## Start your own in three renames

```bash
cp -R features/Hello features/Weather
```

Then rename the three things that have to agree with each other:

| Where | From | To |
| --- | --- | --- |
| the directory | `features/Hello/` | `features/Weather/` |
| `feature.php` | `'key' => 'hello'` | `'key' => 'weather'` |
| every class | `\Nino\Modules\Hello` | `\Nino\Modules\Weather` |

The class is never declared anywhere: `features/Weather/` can only ever serve
`\Nino\Modules\Weather`, which is what makes a feature findable without a
registry. Rename the test and its `hello/` action names and text keys too, and
you have a feature of your own that passes its own test on the first run.

## What is in here, and why

```
feature.php                    the manifest — read this first
Hello.php                      the runtime class: init(), upgrade(), the shortcode
Admin/Admin.php                the /_admin panel: declaration, then two actions
assets/admin.js                what the panel draws
assets/admin.css               …and the little it adds to the workbench's own look
assets/hello.css               what [hello] needs on the site itself
install/manifest.php           what activation puts in the project
install/templates/…            the page it installs
install/text/<locale>.php      the words the *site* says
text/<locale>.php              the words the *panel* says
tests/hello-smoke.php          the feature's own test
```

Three pairs in there are worth pointing at, because each is a decision rather
than a convention.

**`install/text/` and `text/`** are both text files and neither is the other.
`install/text/` is merged into the project's own `text/<locale>.php` at
activation, add-only: from then on an editor keeps those words current in the
Text panel and never opens a feature directory. `text/` is the panel's own,
read straight out of the feature while the panel is drawn — the workbench's
words, which an operator has no business editing and an editor never sees.

**A setting and a panel.** The greeting is a setting declared in `feature.php`;
the Features panel draws and stores it and this feature writes no code for it at
all. The name is stored by `Admin/Admin.php` in `data/hello.php`. The line
between them: a setting is *one value that belongs to the site*; a panel is
anything with more than one row, anything that needs a button, and anything that
is content rather than configuration.

**A runtime route and an installed one.** `Hello::init()` registers
`GET://hello` on every request, so it vanishes when the feature is deactivated.
An install unit can write a route into `config.php` instead, and then it stays
behind as the project's own. Which you want depends on one question: is the page
still meant to exist when your code is gone? For a page an editor fills, yes.
For one that only works while your code runs it, no.

## The parts, in the order a request meets them

**`feature.php`** — the manifest. Key, name, description, the manual an operator
reads, the category, the version, which Nino it needs, what it requires, what it
stores and what it can be configured with. Nothing here runs.

**`Hello::init()`** — called on every request while the feature is active.
Registration only: a shortcode, a route, an asset. Never do I/O here. It runs for
the whole site, including every page that never renders a single thing of yours,
so a `stat()` in `init()` is a `stat()` on every request.

**`Hello::upgrade()`** — the only hook there is, called once when the version in
`feature.php` is newer than the one recorded. Migrate stored data, guarded by the
version each step belongs to. There is no install hook: activation *is* the
install unit, so anything a fresh install also needs goes there instead.

**`Admin/Admin.php`** — the panel. Everything above `apiList()` is declaration:
the permission, the actions, the menu entry, the icon, the panes, the assets, the
text directory, and what the activity log records. Below it are the two methods
that are the screen — one that says what is there, one that takes what comes
back. Both guard themselves on their first line, because the routing does not: a
panel that is not drawn is not a panel that cannot be posted to.

**`install/manifest.php`** — what activation puts in the project, add-only. The
whole vocabulary is `routes`, `templates`, `files`, `elementTypes`, `blacklist`
and `config`, plus the text files beside it. Activating twice changes nothing.

## Two rules that are never optional

**Escape what came from outside; do not escape a fill.** `[[…]]` goes through the
fill engine, which is what an editor's own text is written in. A shortcode
argument comes straight out of a template and a panel's stored value straight out
of a form — both are escaped, always. `Hello::doShortcode()` shows both in four
lines.

**The screen validates to be kind; the server validates to be right.** Do both.
An error that arrives before the request is a better error, and the only check
that counts is the one on the server.

## Trying it

Activate it in the Features panel, then:

- `[hello]` on any page greets the world — or whoever the **Hello World** panel
  names, with whatever word the row's **Settings** hold.
- `[hello name="Ada"]` names somebody for that one page.
- `/hello` is a page of its own, installed with the feature.

## Tests

`tests/hello-smoke.php` runs against a real Nino checkout with a throwaway
project in it — the manifest, the activation and what it merged, what `init()`
registered, what the shortcode renders and escapes, the panel's guards and its
two actions, what is stored and where, the upgrade hook, and what deactivation
leaves behind. It is laid out in that order, so copy the shape and delete the
sections your feature has no equivalent of.

```bash
php features/Hello/tests/hello-smoke.php
NINO_ROOT=../nino php features/Hello/tests/hello-smoke.php
```

## Should this be in the catalogue at all?

It is, filed under `system`, because a template nobody builds and nobody tests
rots — here CI runs its test on every push and `bin/build.php` holds its manifest
to the same rules as everything else. A developer can also install it, see it
work, and then copy it. If you would rather it were not offered to operators,
moving the directory out of `features/` is the whole change; nothing else refers
to it.

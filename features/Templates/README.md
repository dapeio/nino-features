# Template Builder

**Key:** `templates` · **Class:** `\Nino\Modules\Templates` · **Version:** 1.0.0 · **Nino:** `^1.2`

Builds the project's `page-*.tpl` files out of whole sections. The panel lists
the page templates, opens one as a stack of sections, and inserts a section
from a library of seventeen ready-made ones - each with a live preview and its
own fields to fill in.

The visible unit is always a complete `<section>`, never an arbitrary node.
What the builder does not recognise it leaves byte for byte: a page frame, a
hand-written block, anything above or below the sections. So a template stays
a file a developer edits, and this panel is one way of working on it rather
than the only one.

Until Nino 1.2 this was a kernel module, switched on in every installation.
It is a feature now: a project that never opens it does not carry it, and a
project that is finished with it can remove it and keep every page it built.

## The panel

**Templates**, in the workbench's **Features** group - a feature's panel always
lands there (see [Features](https://github.com/dapeio/nino/blob/main/docs/_admin.md#features)),
whatever its own `nav()` names. One permission, `/_admin/templates/manage`,
which has to be granted on the roles tab; the Editor role does not carry it.

Three of its actions reach into other panels: creating an Elements type from a
section's model, listing and creating image slots. Those need
`/_admin/types/manage`, `/_admin/elements/manage` and `/_admin/slots/manage`
respectively - an operator without them can use everything else and will be
refused there.

## The section library

`library/<key>/manifest.php` plus one or more `section-*.tpl`. Seventeen ship
with the feature: articles grid, contact form, content section, cta banner,
feature split, filterable grid, fullscreen image, image banner, logo bar,
media split areas, newsletter form, pricing plans, process timeline, static
accordion, static list, static table, and template include.

A manifest declares named areas, the components each area allows, and - for a
repeating area - the Elements model behind it.
[recipe-section-preset.md](docs/recipe-section-preset.md) walks through writing
one.

## Data

None of its own. The page templates it edits are the project's, in
`private/templates/`, and a backup carries them as project content. Switching
the feature off leaves every page exactly as it was; removing it leaves them
too.

## Documentation

| | |
| --- | --- |
| [docs/templates.md](docs/templates.md) | the manual: the panel, the section model, the locked frame |
| [docs/templates.de.md](docs/templates.de.md) | the same in German |
| [docs/recipe-templates-and-pages.md](docs/recipe-templates-and-pages.md) | building a page from templates and sections |
| [docs/recipe-section-preset.md](docs/recipe-section-preset.md) | writing a section preset of your own |

## Tests

`tests/templates-smoke.php` (157 checks) covers the panel, the document model,
the composer and the area model against a real kernel.
`tests/templates-js-smoke.js` (79 checks) covers the client-side model helpers
and the panel's own conventions. `tests/demo-catalogue-smoke.php` (21 checks)
holds the installer's hidden `.demo-catalogue` page to showing every preset
this feature ships.

The changes per version are in [CHANGELOG.md](CHANGELOG.md).

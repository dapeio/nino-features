# Design library

**Language:** English · [Deutsch](README.de.md)

Ten themes, six headers and seven footers - parked here for the **Design** feature, which is not written yet.

## Why this is not in Nino

Up to Nino 1.1 the setup wizard asked four questions about the look of a site: a theme, a header, a footer, and the design values compiled out of them. The catalogue below `_admin/install/library/{themes,header,footer}` is what those four steps read.

Nino 1.2 does not ask. The wizard installs a site and stops there: the base unit delivers one fixed look - `assets/theme.css`, plus the `theme.header.tpl` and `theme.footer.tpl` it is drawn against - and every project starts from the same page. That is a smaller kernel and a shorter wizard, and it puts the look where every other optional thing in Nino lives: in a feature you install when you want it.

The **Design** feature is that feature. It will compile its own stylesheet and replace the base one, and it will offer a catalogue per part of a page - header, footer, sections, typography, articles - rather than one whole-page theme. So this material is not deleted, it is waiting: the ten themes are ten finished sets of decisions, and the thirteen frames are markup and CSS that already work against Nino's grid and its scroll behaviour.

**Nothing here is a feature.** `bin/build.php` and `bin/check.sh` only ever look below `features/`; this directory is never packed into an archive and never listed in `catalogue.json`. It is source material for a feature that will be written next.

## What is here

### `themes/<key>/`

A whole-page look. `manifest.php` names it, describes it for a picker, points at the stylesheet it ships, names the header and footer version it was drawn against, and carries the `design` block the compiled token layer started from:

```php
<?php return [
	'label' 			=> 'Basis',
	'description' => 'The neutral starting point - …',
	'preview' 		=> 'preview.svg',
	'stylesheet' 	=> '/assets/style.theme.basis.css',
	'header' 			=> 'v1',
	'footer' 			=> 'v1',
	'design' 			=> [ 'primary' => '#4faae8', 'harmony' => 1, 'temperature' => 3, /* … */ ],
	'files' 			=> [ 'assets', 'fonts' ],
];
```

Every unit is self-contained: the stylesheet its manifest names, every webfont that stylesheet `@font-face`s, and an SVG preview.

| Key | Name | Header | Footer | What it is |
| --- | --- | --- | --- | --- |
| `basis` | Basis | v1 | v1 | The neutral starting point - a plain white page, one blue accent, and every size straight off the framework scale. The look to pick when the content should do the talking. |
| `bureau` | Bureau | v1 | v3 | A company page that reads as one: a plain bar, cool greys, square corners and a second brand colour one step from the first. For agencies, firms, associations and anything that has to look settled rather than new. |
| `chronicle` | Chronicle | v2 | v3 | Serif body copy on a warm page, a sans headline above it and a tinted band for what is quoted rather than said. Narrow measure, hard corners, no shadows. For magazines, journals, long-form writing and documentation that is read rather than searched. |
| `console` | Console | v6 | v2 | A navigation rail down the side, the smallest root size of the ten, cool greys and a brand-tinted band for notes. Dense, square and wide. For documentation, changelogs, APIs and reference material with many entries. |
| `gallery` | Gallery | v4 | v1 | A wall, not a page: greys with no colour cast at all, an overlay menu behind one mark, wide rows and airy space. The brand appears only where it is put. For photographers, portfolios, exhibitions and anything where the picture is the content. |
| `market` | Market | v5 | v6 | A brand strip across the top and a full-width band through the page in the opposite colour - two loud colours, condensed display type, rounded buttons. For shops, events, campaigns and launches. |
| `midnight` | Midnight | v5 | v2 | Dark in every light: the page sits on the deepest surface rather than following the visitor's system setting, with a third brand colour for what has to stand off it. Rounded and raised. For galleries, product shots, music and anything shown in a dim room. |
| `platform` | Platform | v3 | v7 | Surfaces that lie on each other rather than lines that separate them: a floating bar, raised cards, wide margins and a third colour marking whatever is chosen. For products, apps, SaaS and anything that should read as current. |
| `poster` | Poster | v4 | v4 | Headlines that take the whole width, heavy black bands and an overlay menu - the largest root size of the ten, set tight. One loud colour and no second. For studios, agencies, campaigns and anything meant to be read across a room. |
| `practice` | Practice | v3 | v5 | Rounded, warm and unhurried: the largest root size, gentle contrast, a brand-tinted footer panel and a second colour one step from the first. For practices, studios, local services and anyone whose visitors are looking for reassurance rather than novelty. |

`basis` is the one Nino 1.2 kept: its `design` block and its stylesheet are two of the four sections concatenated into the base unit's `assets/theme.css`, together with `header/v1/style.css` and `footer/v1/style.css`. The site a fresh Nino installs is `basis` - byte for byte what the old wizard produced when you pressed Next four times.

### `header/v<n>/` and `footer/v<n>/`

One page frame each: `template.tpl` and `style.css`, nothing else. The template is what the project's `theme.header.tpl` / `theme.footer.tpl` becomes, included by `html-header.tpl` through `[template /templates/theme.header]`; the stylesheet is what goes into the bundle with it.

| | Variants | What varies |
| --- | --- | --- |
| `header/` | v1 - v6 | A plain bar, a bar with a line, a floating bar, an overlay menu behind one mark, a brand strip, a rail down the side |
| `footer/` | v1 - v7 | From one legal row up to a full column layout with social links, contact block and locale picker |

Two things a header preset has to keep, whichever it is:

- The bar carries `nino-scroll-header`. `_nino/Nino.css` hides it under `body.nino-scroll-down` by taking back `max-height`, `min-height`, both vertical paddings and both horizontal border widths - so a preset must not give the bar a plain `height`, which none of that can take back. A preset that is *not* a bar opts out where it says so: the rail hands `max-height: none` back above its own breakpoint.
- `footer/v2` includes `[template /templates/html-socialmedia]`. That template is in the base unit, so the include resolves in any project - a frame that needs it does not have to bring it.

### `docs/`

[`docs/appearance.md`](docs/appearance.md) ([Deutsch](docs/appearance.de.md)) is the manual of the **Design** panel as Nino 1.1 shipped it - archived here for the same reason the units are: the settings it documents, the `--nino-*` token names they compile into and the surfaces those tokens paint are the contract the Design feature has to answer to.

[`docs/design-feature.md`](docs/design-feature.md) is the concept the Design feature will be built from - what it catalogues, how a set is authored, what it compiles and what it needs from Nino first. German only for now; it is a working document, not a manual.

## Using one today

They are plain files, so a project that wants one takes it by hand: copy the theme's `assets/` and `fonts/` into the project, copy the frame's `template.tpl` over `private/templates/theme.header.tpl` (or `theme.footer.tpl`), append the frame's `style.css` and the theme's stylesheet to `private/assets/theme.css` - or add them to `/nino/html/assets`' bundle as their own entries - and reload. There is no tooling for it, and that is the point: the tooling is the Design feature.

# Design library

**Language:** English · [Deutsch](README.de.md)

Ten whole-page themes from the Nino 1.1 wizard, the archived manual of the panel that compiled them, and the harness the **Design** feature's part sets are designed in.

## Why this is not in Nino

Up to Nino 1.1 the setup wizard asked four questions about the look of a site: a theme, a header, a footer, and the design values compiled out of them. The catalogue below `_admin/install/library/{themes,header,footer}` is what those four steps read.

Nino 1.2 does not ask. The wizard installs a site and stops there: the base unit delivers one fixed look - `assets/theme.css`, plus the `theme.header.tpl` and `theme.footer.tpl` it is drawn against - and every project starts from the same page. That is a smaller kernel and a shorter wizard, and it puts the look where every other optional thing in Nino lives: in a feature you install when you want it.

The **Design** feature is that feature, and it exists now: [`features/Design/`](../features/Design) compiles its own `assets/theme.css` over the base one, out of a set per part of a page rather than one whole-page theme. The six headers and seven footers moved into its library when it was written - they are what a project chooses from, so they ship with it. What stayed here is what the feature does not ship: the ten themes, which are ten finished sets of decisions to read a part set out of rather than to install, and the archived manual of the panel that compiled them.

**Nothing here is a feature.** `bin/build.php` and `bin/check.sh` only ever look below `features/`; this directory is never packed into an archive and never listed in `catalogue.json`. It is source material for a feature that lives one directory over.

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

`basis` is the one Nino 1.2 kept: its `design` block and its stylesheet are two of the four sections concatenated into the base unit's `assets/theme.css`, together with the `v1` header and footer - now [`features/Design/library/header/v1`](../features/Design/library/header/v1) and [`footer/v1`](../features/Design/library/footer/v1). The site a fresh Nino installs is `basis` - byte for byte what the old wizard produced when you pressed Next four times.

### `docs/`

[`docs/appearance.md`](docs/appearance.md) ([Deutsch](docs/appearance.de.md)) is the manual of the **Design** panel as Nino 1.1 shipped it - archived here for the same reason the units are: the settings it documents, the `--nino-*` token names they compile into and the surfaces those tokens paint are the contract the Design feature has to answer to.

[`docs/design-feature.md`](docs/design-feature.md) is the concept the Design feature was built from - what it catalogues, how a set is authored, what it compiles and what it needed from Nino first. German only; it is the working document the discussion ended in, not a manual. The manual is [`features/Design/README.md`](../features/Design/README.md).

## The preview

`preview.php` is a design harness for the part sets - dev only, never deployed, and not part of the feature archive. It boots a real Nino against a throwaway project, applies the base unit into it, compiles the sets and frames you name through the feature's own `\Nino\Modules\Design\Setup` and `Compiler`, and renders one specimen page that touches every class a set can reach. What it shows is therefore what a project gets, not an approximation of it.

```bash
git clone https://github.com/dapeio/nino.git ../nino     # or set NINO_ROOT
php -S 127.0.0.1:8080 design-library/preview.php
```

Then open <http://127.0.0.1:8080/>. The bar along the bottom switches every part, the finetune knob and the root size, and the url says what is on screen - `?section=v4&article=v2:less&step=more&size=l` - so a view is a link you can send somebody. The array at the top of the file is what a plain `/` starts from:

```php
const PARTS = [
	'header' 	=> 'v1',   // features/Design/library/header/v1
	'footer' 	=> 'v3',   // features/Design/library/footer/v3
	'section' => 'v4',   // features/Design/library/sets/section/v4.css
	'article' => [ 'v2', 'less' ],   // set v2, shown at its "less" step
	…
];
```

The throwaway project is rebuilt on every request, so editing a set or a frame is a reload away. It belongs to the request that asked for it and is removed when that request ends, so several php-fpm workers can serve the harness at once. What you see is what a real page renders: the frames go through `\Nino\Html::renderHtml()`, so their textfills, their `[template]` includes and the `[navigation]` shortcode resolve the way they do in a project - the specimen even carries a five-item menu so a header set has something to lay out. A set that does not exist, a frame without a `style.css`, a fill that did not resolve: all of it is named in the bar along the bottom rather than passed over in silence.

The sets and frames themselves are the feature's, in [`features/Design/library/`](../features/Design/library) - its [README](../features/Design/README.md) is where the two rules for writing one are. `sets/<part>/v1.css` there is the starting point for each of the seven parts: it declares nothing, so a fresh preview shows Nino as it is, and lists every rule the framework sets for that part - commented out, with today's values - as the handles that set has. Copy it to `v2.css` and start there.

### Behind nginx

It is a front controller, and that is the whole security model: nginx hands it every request and it answers all of them, so nothing else under the root is ever served - not a template, not a `.php` file, not `.git`.

```nginx
server {
	server_name features.getnino.dev;
	root        /design-preview;              # the checkout

	location / {
		include      fastcgi_params;
		fastcgi_pass unix:/run/php/php8.4-fpm.sock;
		fastcgi_param SCRIPT_FILENAME $document_root/design-library/preview.php;
	}
}
```

There is deliberately no `location ~ \.php$` and no `try_files`: one rule, one script, nothing else reachable. If you put `preview.php` there on its own rather than the whole checkout, point `SCRIPT_FILENAME` at it and set `LIBRARY_DIR` at the head of the file.

**Set `PREVIEW_KEY` before any of that is reachable.** Without one the harness answers the loopback and refuses everybody else, because it boots a kernel and renders unauthenticated. With one, open `https://…/?key=<it>` once: the answer puts the key in an `HttpOnly` cookie and redirects it out of the address bar, so the stylesheet, the fonts and the scripts that follow do not carry it and neither does the browser history. https and an `auth_basic` in front are worth having on top.

The Nino checkout is looked for beside the repository, in `env/`, and under the document root; `NINO_DIR` at the head of the file or the `NINO_ROOT` environment variable name it outright, and a run that finds none says which paths it tried.

## Using a theme today

The themes are plain files and no tooling installs them, so a project that wants one takes it by hand: copy the theme's `assets/` and `fonts/` into the project, append its stylesheet to `private/assets/theme.css` - or add it to `/nino/html/assets`' bundle as its own entry - and reload. The header and footer its manifest names are in the feature's library now, and a frame is a `template.tpl` copied over `private/templates/theme.header.tpl` (or `theme.footer.tpl`) with its `style.css` appended the same way.

Appending anything by hand to a `theme.css` the Design feature compiled is a change the next compile removes, and `Compiler::write()` will refuse the file once it no longer matches its own header. Take the file over deliberately - delete the digest line - or put the theme in the project's own `assets/style.css`, which comes after `theme.css` in the bundle and is nobody else's to write.

# Social links

Links to the profiles a site keeps elsewhere, each with its icon. The links are
elements an editor keeps under **Elements**, and the shortcodes draw them
wherever a template asks for them.

```
[social]
[social show="both" size="large"]
[social only="instagram,youtube"]
Follow us on [social-link instagram].
```

## The links are elements

There is no screen of this feature's own. Activating it copies one element type,
**Social Media** (`/elements/social.php`), and the Elements panel is where the
links are added, changed, hidden and deleted.

| Field | Type | What it holds |
| --- | --- | --- |
| `title` | text, required, at most 60 characters | the name of the network. Drawn as the link's text, or as text only a screen reader reads |
| `icon` | a select, required | which icon the link is drawn with - see [Icons](#icons) |
| `link` | text, required, at most 500 characters | `https://…`, `mailto:…`, `tel:…` or a path on this site, `/contact` |
| `order` | a whole number | the position. Ten apart (10, 20, 30, …), so a link fits between two. `0`, which is what the panel saves for an empty field, goes last |
| `hidden` | yes/no | keeps a link without showing it, anywhere |

Every field is global: a link and the name of a network are the same in every
language. The element ID is what `only=`, `exclude=` and `[social-link]` pick a
link by - the type starts with `instagram`, `facebook`, `youtube` and `telegram`,
which point at the networks' front pages, not at an account: a made-up account
name can be somebody's real one, and `rel="me"` on it would claim it as the
site's. Replace the addresses with the site's own profiles, and delete or hide
the links the site does not have.

The panel shows the fields under the labels `install/text/<locale>.php` merges
into the project's text files - in English and German; a project in another
language sees the field names.

## Shortcodes

Write every attribute value in double quotes - without them the kernel reads
`only=mail` as the bare word `only`.

**`[social]`** - every link that is not hidden, as a list, by position.

| Attribute | Values | What it does |
| --- | --- | --- |
| `only=` | element IDs, comma-separated | only these. An unknown ID is ignored, and the order stays the one of the positions |
| `exclude=` | element IDs | every link but these |
| `show=` | `icon` (default), `both`, `label` | `icon`: the icon, the name for screen readers only. `both`: icon and name. `label`: the name alone |
| `size=` | `small`, `large` | the size of the icons; without it, in between |

**`[social-link instagram]`** (or `id="instagram"`) - one link, icon and name,
without a list around it: for running text. `show=` works here too.

**`[social-icon telegram]`** (or `name="telegram"`) - the icon alone, for markup of
a template's own. Inside an Elements block it draws each element's:

```
[elements /social query="hidden=0" sort="order"]<li>[social-icon name="[[icon]]"] [[title]]</li>[/elements]
```

Where the links themselves are wanted, `[social]` and `[social-link]` are the
ones to use: they check an address before it becomes a link, and the core's
`[elements]` does not - an editor's `javascript:` would go out as it was typed.

What `[social only="instagram"]` writes:

```html
<ul class="nino-social"><li class="nino-social-item"><a class="nino-social-link" href="https://www.instagram.com/" rel="me"><span class="nino-social-icon" aria-hidden="true"><svg …>…</svg></span><span class="nino-social-label nino-sr-only">Instagram</span></a></li></ul>
```

- **Nothing to draw, nothing drawn.** Where no link is left - all hidden, an
  `only=` that matches none, a type the project does not have - the shortcodes
  write nothing at all, not an empty list.
- **An address is checked before it is a link.** `http(s)://` with a host and
  nothing before it, `mailto:`, `tel:`, or a path on this site that does not
  start with `//` or `/\`. Anything else - `javascript:`, `data:`, an address
  with a space, a tab or a line break anywhere in it - is left out without a
  word.
- **Every link has a name.** A name is escaped, its `[` neutralised, so it can
  neither be markup nor a fill nor a shortcode. A link without one is named by
  its host (`instagram.com`), or by the address after `mailto:`/`tel:`.
- **`rel="me"` on profiles.** A link to `http(s)://` carries it - it is how a
  profile that links back, Mastodon's, verifies that it is the site's. Mail,
  phone and paths on the site do not.
- **The same tab.** Nothing opens a new one.

## Icons

From [Lucide](https://lucide.dev), one svg per file in `icons/`, cleaned to one
line in `currentColor`, `aria-hidden` and without an id, so the same icon can be
on a page twice. The link's colour is the colour of the text around it; the name
beside the icon is the link's text, so the icon is decoration to a screen reader.

Lucide has drawn no logos of brands since its 1.0, and never drew some at all. Six
come from its last version that had them, 0.577.0, in the same line style and
under the same license; the others are drawings that say what the network is
for:

| Option | Lucide icon | Version |
| --- | --- | --- |
| `instagram`, `facebook`, `linkedin`, `youtube`, `github`, `twitch` | the brand's own | 0.577.0 |
| `telegram` | `send` | 1.49.0 |
| `whatsapp` | `message-circle` | 1.49.0 |
| `mastodon` | `message-square` | 1.49.0 |
| `threads` | `at-sign` | 1.49.0 |
| `bluesky` | `cloud` | 1.49.0 |
| `tiktok` | `music` | 1.49.0 |
| `xing` | `briefcase-business` | 1.49.0 |
| `pinterest` | `pin` | 1.49.0 |
| `website` | `globe` | 1.49.0 |
| `mail`, `phone`, `rss` | the same | 1.49.0 |
| `podcast` | `mic-signal` | 1.49.0 |
| `blog` | `pen-line` | 1.49.0 |
| `shop` | `store` | 1.49.0 |
| `map` | `map-pin` | 1.49.0 |
| `link` | the same - also what a link is drawn with whose icon is none of these | 1.49.0 |

A file is named after what it is for, not after the drawing: replacing
`icons/telegram.svg` changes every Telegram link, and no element has to change.
An icon is a file of this feature's, never something an editor types - the
element only picks one by name.

`icons/LICENSE` is Lucide's license (ISC, and MIT for the icons Lucide took over
from Feather), `icons/LICENSE-0.577.0` the one the six brand icons were published
under.

## Install unit

Applied add-only at activation - a file or a key the project already has is
left exactly as it is:

| File | What it is |
| --- | --- |
| `elements/social.php` | the element type, with the four links to start from |
| `templates/social-links.tpl` | holds `[social]`. The Design feature's header `v7` and footers `v2`, `v8` and `v10` include it, and draw nothing where it is missing - which is why they include a template rather than naming the shortcode: without this feature, `[social]` would stand on the page as text |
| `text/<locale>.php` | the five labels of the type's fields for the Elements panel, put on the blacklist so the Text panel does not offer them as the site's words |

A link the editors deleted stays deleted when the feature is updated or switched
on again, and a type of the same name the project had before is kept - the
shortcodes then draw what of it fits, which may be nothing.

## Assets

`assets/social.css` joins the site's own bundle, `/.cache/style.css`. It lays the
list out in a row, sizes the icons and keeps every link in a list at least 24px
across - the smallest target WCAG 2.5.8 accepts; a link in running text is as
tall as its line, which the same rule allows. Out of a list the icon sits on the
line the way a capital does, so `[social-link]` and `[social-icon]` keep the
words around them on their baseline. It sets `fill: none` on the icons: a rule
elsewhere that fills svgs would turn a line icon into a blot.

## Settings

None. Which links, how they show and how large is said where they are drawn - a
header and a footer usually want different answers.

## Data

None of its own: the links are elements, in the project's `elements/social.php`,
which a backup carries like every other type.

## Switching it off

Deactivating removes the shortcodes and the stylesheet and nothing else: the
type, the links, the labels and `templates/social-links.tpl` stay, and switching
it on again finds them. Like every feature's, a shortcode of a feature that is
off is left on the page as it was written - so empty `templates/social-links.tpl`
and take `[social…]` out of the pages first.

## Tests

`tests/social-smoke.php` - the manifest, the icons (one per option, every one
clean) and their license, the labels; the activation and what it copies; what
the three shortcodes draw and every attribute; the positions; what an editor can
put into an element - unsafe addresses, markup and a shortcode in a name, an icon
that is none of the feature's, a hidden link asked for by name - and what of it
reaches the page; add-only over a project's own files, an update, a project in
French alone; and deactivation.

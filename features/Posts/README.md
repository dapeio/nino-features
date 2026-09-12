# Posts

**Key:** `posts` · **Class:** `\Nino\Modules\Posts` · **Version:** 1.0.0 · **Nino:** `^1.1`

A page per element, and a list with paging. What turns an element type into a
section of the site - a blog, a news column, a journal.

Nino has the content half of that already. An element type is records of one
shape, the Elements panel edits them, `[elements]` lists them, Search indexes
them, a backup carries them and the Translations export knows about them. What
it has no answer for is the other half: every record wants a page of its own at
a readable url, the list wants to be more than one page long, and the page
title wants to be the post's rather than the route's.

So this owns no content. A section names an element type and a path; the routes
follow from that, and the records stay the project's - written where every other
element is written, and still there when the feature is gone.

## A section

```
/blog                     the list, newest first, ten to a page
/blog/ohne-datenbank      one post
/blog?page=2              the rest
```

A section is an element type, a path and the two templates that render them:

| | Default | What it is |
| --- | --- | --- |
| `type` | `/posts` | the element type the section publishes |
| `path` | `blog` | the public path - `/blog` and `/blog/<slug>` |
| `index` | `/templates/page-posts` | the list page's template; empty registers no list route at all |
| `post` | `/templates/page-post` | the post page's template |
| `sort` | `-date` | the order, as `[elements]` reads it - several fields, `-` reverses |
| `date` | `date` | the field that dates a post; empty publishes everything |
| `title` | `title` | what the page's own `<title>` becomes |
| `summary` | `summary` | ...and its meta description |
| `image` | `image` | the field `[[.image]]` renders |
| `alt` | `imageAlt` | ...and the one its `alt` comes from |
| `body` | `body` | the field `[[.body]]` renders as paragraphs |
| `perPage` | 10 | how long a page of the list is |

They live in `data/posts.php`, and a project that has said nothing has exactly
the section above - installing the feature is enough to have a blog. An empty
list in a file that exists is a real answer, and stays one.

A **post dated in the future is not published**: it is not in the list, and its
own url is a 404. So a post can be written today and appear on Monday without
anything having to run on Monday. A post whose date field is empty is a draft,
for the same reason. A section with no `date` field publishes everything it has.

### The routes

Both are registered per request out of `data/posts.php` rather than written
into `config.php`, so they follow the section rather than a copy of it made at
install time - and they go away with the feature instead of leaving a path
nothing answers. Two consequences worth knowing:

- The **Routes panel does not list them.** They are the module's, the way the
  Seo feature's `sitemap.xml` is.
- A page the project already has under that path **keeps everything it says** -
  its menus, its locale, the identity its texts hang off - and only what it
  renders becomes the section's list. Remove the feature and the page is back
  exactly as it was. A section with an empty `index` leaves even that alone:
  the project's own page carries `[posts]` itself.

The post route is a wildcard (`GET://blog/*`), which is how one route serves
every post. A slug that is not one segment of `[A-Za-z0-9][A-Za-z0-9._-]*` never
reaches the filesystem, and a slug nobody has is the project's own 404 page -
not a blank page with a header on it.

## The four shortcodes

Inside every block the fields are Elements' own - `[[title]]`, `[[date]]`,
escaped exactly the way `[elements]` escapes them - plus the three values an
element cannot know by itself.

```
[posts]…[/posts]              the page of the list that is on
[post]…[/post]                the post this page is
[posts-pager]                 the way to the next page
[post-nav]…[/post-nav]        the way to the next post
```

| | What it is |
| --- | --- |
| `[[.url]]` | the post's own url - the element knows its uri, the section knows the path |
| `[[.image]]` | the whole `<img>`, sized out of the model, or nothing where there is no picture |
| `[[.body]]` | the body field as paragraphs, see below |
| `[[.id]]` | the post's number in the list, from 0 |
| `[[.rel]]` | `prev` or `next`, inside `[post-nav]` only |

`[posts]` takes `section="news"` where a project has more than one, and
`limit="3"` turns the paging off - the three newest posts on a front page are a
teaser, not page one of something. `[posts-pager]` renders nothing when there is
only one page: saying "1 of 1" is telling somebody there is more. It writes the
markup `.nino-pagination` in `Nino.css` is written against, and its three words
come from the attributes `prev`, `next` and `label`, else from the textfills
`/posts/prev`, `/posts/next` and `/posts/label`, else from its own English and
German.

### The body

Nino's html fields are deliberately flat - `strong`, `em`, `span`, `code` and
`a`, and nothing that makes a block (`\Nino\Html::sanitizeHtml()`, and the
editor beside it offers exactly those). That is the right decision for a field
and the wrong shape for an article, so `[[.body]]` adds the one thing missing
and nothing else: **a blank line starts a paragraph, a single one is a break**,
and every paragraph goes through the kernel's own sanitiser exactly as it is.

No tag is allowed there that a field could not carry anyway. The `<p>` and the
`<br>` are this feature's own markup, written around text the kernel has already
cleaned, and each paragraph carries `nino-section-text` - the framework's body
copy, because a bare `<p>` has no margin in Nino and a set of the Design feature
that styles a section's text then styles a post with it.

A body that wants headings, lists and pictures is a page rather than a field,
and the post's own template is where those go.

## What it installs

| | |
| --- | --- |
| `/elements/posts.php` | the element type a section is: title, summary, date, author, image, imageAlt, body, tags |
| `/templates/page-posts.tpl` | the list |
| `/templates/page-post.tpl` | the post, with its previous/next |
| `/text/<locale>.php` | the eight words those two say |

Nothing is overwritten: a project that already has an element type called
`posts`, or a template of these names, keeps every one of them (see
`\Nino\Features::activate()`). The posts themselves are ordinary elements, so
`/elements/` is where a backup already finds them - `data/posts.php`, which is
only the sections, is what this feature declares.

## Tests

`tests/posts-smoke.php` (54 checks) covers the manifest, what a section is
normalised to and what it refuses (a type, a path or a template that could climb
out of the project; two sections under one path), the install unit, the two
routes and the merge into a page the project already has, the slug resolution
and its four ways of being a 404, the page title a post takes over, the paged
list and its pager, `[post]`, `[post-nav]`, the escaping a title goes through,
the picture as a whole tag or as nothing, and the body in paragraphs.

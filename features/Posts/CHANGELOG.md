# Changelog

All notable changes to the Posts feature are documented in this file.
A release is the tag `posts-<version>` of dapeio/nino-features.

## Unreleased

### Fixed

- **A post body ran the shortcodes an editor typed.** Every other field value
  swaps its `[` for `&#91;` on the way into the page, because the block is
  rendered again afterwards - `[[.body]]`, which takes the same field through
  its own paragraph template, did not, and neither did the `alt` of
  `[[.image]]`. A body containing `[template /templates/page-whatever]` ran
  it. Both take the same two steps as every other value now.

## 1.1.0 — 2026-09-12

- **The section and its posts are in `sitemap.xml` and `llms.txt` now.** A
  section's routes are registered per request out of `/data/posts.php`, and
  the posts themselves are one wildcard route standing for every record -
  so the SEO feature, which reads the site's pages out of `config.php`, could
  find neither. This feature answers that feature's `/seo/pages` callback
  with the index and every published post.
- A post brings its title and summary with it, because the page it belongs to
  has no textfills of its own, and its own date as the `<lastmod>`, because it
  has no template to take an mtime off. The index is dated by the newest post
  it lists. A post that is not published yet is offered to neither document,
  the same rule the page itself follows.
- Registered whether or not the SEO feature is installed: a callback nobody
  fires costs one array entry.

## 1.0.0 — 2026-09-12

First release: a page per element, and a list with paging.

### The section

- A section names an element type and a path, and the two routes follow from
  that: `/blog` for the list and `/blog/<slug>` for one post, the second as the
  wildcard route the kernel walks parent paths for. Stored in `data/posts.php`;
  a project that has said nothing has one section called `blog` over `/posts`,
  so activating the feature is enough to have a blog.
- Both routes are registered per request rather than written into `config.php`.
  They follow the section rather than a copy of it made at install time, and
  they go away with the feature instead of leaving a path nothing answers.
- A page the project already has under the section's path keeps everything it
  says - its menus, its locale, the identity its texts hang off - and only what
  it renders becomes the list. An empty `index` leaves even that alone.
- A post dated in the future is not published: not in the list, and a 404 at its
  own url. An empty date is a draft. A section with no date field publishes
  everything it has.
- A slug that is not one segment of `[A-Za-z0-9][A-Za-z0-9._-]*` never reaches
  the filesystem, and a slug nobody has is the project's own 404 page.
- A post's own title and summary become the page's `<title>` and meta
  description for that request - the runtime fills win over the route's text
  keys, which stay as the fallback for a post that has neither.

### The shortcodes

- `[posts]` is the page of the list that is on (`?page=2`), `[post]` the post
  the url is for, `[posts-pager]` the way to the next page and `[post-nav]` the
  way to the next post. The field vocabulary is Elements' own, escaped the same
  way, plus `[[.url]]`, `[[.image]]`, `[[.body]]`, `[[.id]]` and `[[.rel]]`.
- `[posts limit="3"]` turns the paging off - a teaser is not page one.
  `[posts-pager]` renders nothing where there is one page, and writes the markup
  `.nino-pagination` in `Nino.css` is written against.
- `[[.body]]` turns blank lines into paragraphs and single ones into breaks,
  each paragraph through `\Nino\Html::sanitizeHtml()` unchanged. No tag is
  allowed that a field could not carry anyway; the `<p>` and the `<br>` are this
  feature's own markup around text the kernel has already cleaned.
- `[[.image]]` is the whole `<img>`, sized out of the model, or nothing at all -
  a template written against `[elements]` has to say `src="…/images/[[image]]"`
  and gets a broken picture on every post that has none.

### The install unit

- `/elements/posts.php` (title, summary, date, author, image, imageAlt, body,
  tags), the two templates and the eight words they say. Nothing is overwritten.
- `data/posts.php` is the only thing declared under `data`: the posts are
  ordinary elements, and `/elements/` is where a backup already finds them.

### Found while building it

- **A blog that left its own navigation.** Registering the section's list route
  over the project's dropped that route's `navs`, so installing the feature took
  the blog out of the menu it was in. The route is merged now, not replaced -
  found in a browser, after a test had agreed with the broken version.
- **A test that agreed with the implementation rather than with the kernel.**
  The request copy `\Nino\Http::request()` pushes onto `./nino/http/requests` is
  made *before* a route is matched, so its response uri is still the path that
  was asked for. The section is resolved from the request path now, and the
  test's fixture says what the kernel really stores.

### Tests

- `tests/posts-smoke.php`, 54 checks.

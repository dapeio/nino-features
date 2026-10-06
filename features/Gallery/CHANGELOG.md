# Changelog

All notable changes to the Gallery feature are documented in this file. The
format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), the
versions [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## Unreleased

- **The album list is the design system's row of buttons.** A row used to
  be a card of its own with two buttons on it, a red Delete first - the one
  thing on a list a hand hits by mistake - and looked like nothing else in
  the workbench. The list has the shape every other list a screen drills
  into has now: the whole row opens the album, a chevron says so, and
  Delete lives on the album's own screen, in an action bar under the grid,
  where the pictures it takes with it are in view. `gallery-js-smoke.js`
  draws the list and holds it.

- Needs Nino `^1.3`, where the constraint said `^1.1`. The sectioned `manual`
  map this manifest carries is only read by a kernel newer than the
  `v1.2.0-beta` tag - `Features::manifest()` refuses it on the tagged one -
  and `^1.1` is satisfied by that kernel, so the catalogue offered the
  feature to an installation that could not then install it. `^1.3` names
  only a kernel that can read the manifest.

### Added

- **An image has an alt text of its own, and both texts are per language.**
  The caption was the only text an image had, and it served as the caption
  under the picture, as `data-caption` and as the thumbnail's `alt` at once.
  Every image carries an `alt` beside its `caption` now, each either one
  string for every language or a map of locale to text, the shape the images
  cluster plans for the alt of an image slot. The shortcode shows the current
  language's text, else the site's native one, else the first there is.
  The thumbnail's `alt` is the alt text, or the caption where there is no alt
  text; an image with an alt text and **no** caption gets an empty
  `data-caption`, which the Lightbox now reads as "no caption" instead of
  printing the alt text under the picture. A project that never touches an
  alt text sees the same markup as before.

- **A language switch in the album's toolbar.** The workbench's own, the one
  the Elements and Text screens have, hidden where the project has one
  language. Each tile has an alt text field and a caption field for the chosen
  language, each saved when it is left. The first text written for a language
  on an image whose text was one string keeps that string for every other
  language; clearing a field takes that language's text away.

- **A refused upload says why, and names the limit.** Every refusal was "That
  image is not in this album." - a text meant for something else. The panel
  answers *the file is larger than 2 MB* for a file over `upload_max_filesize`
  and for one over the 8 MiB the kernel makes an image from, *not a JPEG, PNG,
  GIF or WebP image*, *too many pixels - at most 20 megapixels*, and *the
  server could not make the two sizes*. The kernel's cap is private to
  `\Nino\Images`, so the number is mirrored in `Gallery::KERNEL_UPLOAD_BYTES`
  and only ever used to say why, never to refuse; the pixel limit is the
  kernel's public `MAX_SOURCE_PIXELS`. "That image is not in this album." stays
  for the image actions it is true for.

- **A file that is too big is stopped in the browser, by name.** A file over
  `post_max_size` never reaches the feature: php drops the whole request with
  its CSRF field, and what came back was the CSRF check's refusal, shown as
  "The file could not be read." The panel compares each file with php's limit
  (`gallery/list` answers it) before sending it, and stops the batch at the
  first one over, saying which. Every message of an upload now starts with the
  file's name.

### Changed

- **Saving a text is one locked write.** `gallery/image-save` read the whole
  file, changed one image and wrote the file back, so two people saving two
  images could lose one of the changes. It goes through
  `\Nino\Filesystem::mutate()` now. It takes `{ album, id, locale, alt,
  caption }` - a language the project has is required, and a text that is not
  posted is left as it is. The other actions still write the album file whole.

- **A downgrade needs the data looked at first.** Earlier versions of this
  feature cast a caption with `(string)`, and a map where they expect a string
  raises *Array to string conversion*, which the kernel treats as fatal. A
  project that saved a text for a language and goes back to an earlier version
  has to turn those maps into strings in `/data/gallery.php` by hand first.

- **Nothing for a project to run.** The strings already stored stay valid, so
  there is no `upgrade()`.

- **The panel's texts.** New fills in `text/en_US.php` and `text/de_DE.php`:
  `label/alt`, `label/locale`, `error/locale`, `error/size`, `error/type`,
  `error/pixels` and `error/process`. A project that overrides a panel text
  has nothing to change.

- **The README's "Styling" chapter counted four custom properties and named
  three.** `gallery.css` declares `--nino-gallery-columns`,
  `--nino-gallery-gap` and `--nino-gallery-radius` on `.nino-gallery`, and
  those three are the ones the chapter lists. It says three.

- **The comment naming the two patterns stood over `TEMPLATES`.** "An album
  key, and the id an image is filed under" describes `KEY_PATTERN` and
  `ID_PATTERN`; the constant the template patch added was put between it and
  them, with its own comment, so the file read as though `TEMPLATES` were the
  album key. Each comment stands over what it explains again.

- **The gallery's markup is a template now.** The list and an item of it were
  strings `Gallery.php` built; they are `templates/gallery.tpl` and
  `gallery-item.tpl`, filled by token with every value escaped before it goes
  in - see AGENTS.md, "Markup belongs in a template". The output is the same.

### Fixed

- **Every thumbnail was cut off at the bottom, and the grid restyled the
  design system's mosaic.** The list was `.nino-gallery` and an item
  `.nino-gallery-item` - the names of Nino.css's public mosaic grid, which
  lands in the same `/.cache/style.css` bundle with fixed rows of 160 and
  200 px and an item that clips what overflows. Rendered, a 500 × 500
  thumbnail in a 292 px column stood in a 200 px cell and lost its lower
  92 px; on a phone a 173 px thumbnail lost 13 px; and on any page that
  used the kernel's mosaic, this stylesheet's margin, padding and column
  formula won over the kernel's. The list is `.nino-gallery-grid` now and
  an item `.nino-gallery-cell`; `.nino-gallery-link`, `.nino-gallery-thumb`
  and the three custom properties keep their names. A project stylesheet
  that addressed `.nino-gallery` or `.nino-gallery-item` for this grid
  addresses the two new names - a documented styling hook changes, so the
  next release is a minor one. The suite holds that no class the templates
  or the stylesheet write is one Nino.css styles.

- **A caption could not be put back to what it was.** A tile is not rebuilt
  after its caption was saved - the cursor is in the field - so the image
  object the tile was drawn from stayed at the caption the screen was drawn
  with. Leaving the field compares against that one, so clearing a caption
  that was empty when the album was opened read as "nothing changed" and was
  never sent, while the server went on holding what it had been sent in
  between; every other blur sent the same caption again for the same reason.
  The record says what the field last sent now.

- **An upload that failed halfway hid the pictures that had arrived.** The
  files are uploaded one after the other, and where one of them failed the
  grid was left as it was before the batch - so pictures that are on the
  server and in the panel's own list were nowhere on the screen until
  something else redrew it. The album is drawn again on that path too. What
  the panel says about an upload is written after that drawing rather than
  before it: the drawing builds a new upload field, and the finished batch's
  "%s added." was being written on the field it replaced.

- **An invalid byte in a value rendered as nothing.** `htmlspecialchars()`
  answers input that is not valid UTF-8 with `''` unless `ENT_SUBSTITUTE` is
  among its flags, and every call here spelled the flags out without it.
  Every call carries it now, as the kernel's do, and
  `tests/escaping-smoke.php` reads every feature for the next one.

- **A long caption could break the whole panel.** The length caps on a
  caption and an album name were counted in bytes with `substr()`, which
  splits a multibyte character when the cut lands inside one - and
  `json_encode()` answers a string that is not valid UTF-8 with `false`, so
  the panel's whole reply came back empty and every screen of it stopped
  working until somebody found the caption by hand. Both cuts land on a
  character boundary now.

## 1.0.0 — 2026-09-09

First release.

- Albums in `/data/gallery.php`, rendered with `[gallery album="…"]` as a grid
  of thumbnails that open full screen through the Lightbox feature.
- Two derived sizes per upload - a cropped thumbnail and an uncropped large
  view - both through `\Nino\Images`, so a project that registers on
  `\Nino\Images::RENDER` renders these too. The uploaded file is never stored.
- The **Gallery** panel: albums, multi-file upload, captions that may be
  textfills, order, and deletions that take the pictures with them.

# Gallery

**Key:** `gallery` · **Class:** `\Nino\Modules\Gallery` · **Version:** 1.0.0 · **Nino:** `^1.3` · **Requires:** [`lightbox`](../Lightbox/README.md)

Any number of image galleries. An album is a key, a name and a list of
pictures; `[gallery album="trip"]` renders it as a grid of thumbnails that
open full screen.

Two sizes are made when an image is uploaded, **and neither of them is the
upload**. The thumbnail is cropped to exactly the configured size, because a
grid of pictures that are all different shapes is not a grid. The large view
is the whole picture scaled into a box, keeping its own proportions - because
cropping is what somebody opening a thumbnail wanted undone.

The original bytes are never stored. What a visitor can reach is the largest
thing that exists: there is no full-resolution copy of somebody's camera file
sitting under `/images` waiting to be guessed at, and no exif riding along
with it either, since every image is re-encoded from pixels by
`\Nino\Images`.

The overlay a thumbnail opens into is the [Lightbox](../Lightbox/README.md)
feature's, which this one requires - installing this from the catalogue brings
it along. What is rendered here is the markup that feature reads, so a project
could swap the overlay without touching the gallery.

## The shortcode

```
[gallery]                             the first album defined
[gallery album="trip"]                a particular one
[gallery album="trip" columns="3"]    …with a grid of its own width
```

It renders one `<li>` per image: a link to the large view carrying
`data-lightbox="gallery-<album>"` - so two galleries on one page stay two sets
- with the image's **caption** as `data-caption` and its **alt text** as the
thumbnail's `alt`. An album that does not exist, or has no images yet, renders
nothing at all.

The two are texts of their own, because they are for different readers: the
alt text says what the picture shows to somebody who cannot see it, the
caption is what is printed under it in the overlay.

- An image with no alt text gets its caption as the thumbnail's `alt`, so the
  link always has a name.
- An image with an alt text and **no caption** gets an empty `data-caption`,
  which the [Lightbox](../Lightbox/README.md) reads as "no caption" - the alt
  text is not printed under the picture a second time. The overlay's own
  picture carries the thumbnail's `alt`.
- An image with neither gets no `data-caption` and an empty `alt`.

Either may be written as a textfill (`[[/project/gallery/pass/caption]]`), which is how
one text serves every language. It is resolved when the gallery is rendered
and escaped on the way into the attribute.

Each is stored as one string - the same for every language - or as a map of
locale to text, `{ "de_DE": "Über dem Pass", "en_US": "Above the pass" }`. The
page shows the current language's text, else the site's native language's,
else the first one there is, else nothing.

## The panel

**Gallery**, in the Features group, behind `/_admin/gallery/manage`.

The list is one row per album with its shortcode and how many pictures it
holds. A row leads to that album's own screen: what an upload will become
(said before the upload, not after), the file field - several files at once,
uploaded one after the other - and the pictures as a grid, each with its
alt text and its caption, two buttons to move it, and one to delete it.

A project with more than one language has the workbench's language switch in
the album's toolbar - the same one the Elements and Text screens have, and
the choice is shared with them. The alt text and the caption fields show and
save that language's text; each is saved when the field is left. A text that
was one string for every language stays every language's until the first one is
written - then each language keeps the old text, and the one that was edited
is the new one. Clearing a field takes that language's text away.

### When an upload is refused

The panel says why, with the limit where there is one:

| Message | When |
| --- | --- |
| *The file is larger than …* | the file is over what php takes in one upload - the smaller of `upload_max_filesize` and `post_max_size` - or over the 8 MiB the kernel makes an image from |
| *The file is not a JPEG, PNG, GIF or WebP image.* | the bytes are not one of those |
| *The image has too many pixels …* | more than 20 megapixels (`\Nino\Images::MAX_SOURCE_PIXELS`) |
| *The server could not make the two sizes …* | the file passes all of that and gd, or a handler on `\Nino\Images::RENDER`, still refused it |
| *The file could not be read.* | php reported any other upload error |

A file over `post_max_size` never reaches the feature: php drops the whole
request, the CSRF field with it, and all that comes back is the CSRF check's
refusal. So the panel compares each file with php's limit before it sends it,
stops the batch at the first one that is over, and names it - every message
of an upload starts with the file's name. The 8 MiB of the kernel is checked
by the kernel only; this feature mirrors the number to explain a refusal, never
to make one.

Deleting an image takes both of its files with it. Deleting an album takes
every picture in it: unlike a form's submissions, an album's images *are* the
album, and leaving them behind would leave files nobody can reach from the
workbench again.

## Settings

| Setting | Default | What it does |
| --- | --- | --- |
| **Thumbnail width / height** | 500 × 500 | the thumbnail is cropped to exactly this |
| **Large width / height** | 1800 × 1800 | the box behind a thumbnail |
| **Keep the original ratio** | on | on, the large view is the whole picture scaled into that box; off, it is cropped to it exactly, like the thumbnail |
| **Columns** | 4 | thumbnails per row at full width; the grid falls to fewer on a narrow screen by itself |

Changing a size applies to uploads from then on. The pictures already there
keep the size they were made at - their filenames carry it, and re-cropping
what is on disk would mean re-cropping something already cropped.

## A richer uploader, later

Both sizes go through `\Nino\Images::process()` and `\Nino\Images::fit()`,
which fire `\Nino\Images::RENDER` after the safety checks and before the
encoding. A project or a feature that wants a srcset or an imagick pipeline
registers there and renders this feature's images too - without this feature
knowing anything about it, and without a fork of anything. Webp is the
kernel's own output now, so both sizes are `.webp` wherever gd can write one. See
[Callbacks](https://github.com/dapeio/nino/blob/main/docs/development.md#callback-reference)
in the developer manual.

## Data

`/data/gallery.php` holds the albums, their names and each image's alt text and
caption - the file the manifest declares, so a backup carries it. Saving a
text writes the file under a lock, so two people saving two images do not
overwrite each other. The pictures live under
`/images/gallery/<album>/`, where every other uploaded image lives and where a
backup already carries them.

## Styling

The grid reads three custom properties off `.nino-gallery-grid`:
`--nino-gallery-columns` (written by the shortcode), `--nino-gallery-gap` and
`--nino-gallery-radius`. The classes the markup carries - `.nino-gallery-grid`,
`.nino-gallery-cell`, `.nino-gallery-link` and `.nino-gallery-thumb` - are this
feature's own; Nino.css's `.nino-gallery` is the design system's mosaic, another
grid, and neither styles the other. The overlay is the Lightbox feature's and
has two of its own.

## Tests

```bash
NINO_ROOT=../nino php features/Gallery/tests/gallery-smoke.php
node features/Gallery/tests/gallery-js-smoke.js
```

The manifest and its requirement, the two sizes and the one thing that is
never stored, the render callback carrying this feature's images too, the
panel with its albums, alt texts and captions per language, order and
deletions, every refusal of an upload with the limit it names, and the markup
the Lightbox reads.

`gallery-js-smoke.js` is the panel's own script over a dom stand-in: the tiles
it draws, the language switch, the text it saves when a field is left and the
one it does not, a file that is too big to be sent, and what an upload of
several files leaves on the screen — when all of them arrive and when one of
them does not. `gallery-smoke.php` runs it too where
`node` is on the path.

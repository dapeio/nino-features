# Gallery

**Key:** `gallery` · **Class:** `\Nino\Modules\Gallery` · **Version:** 1.0.0 · **Nino:** `^1.1` · **Requires:** [`lightbox`](../Lightbox/README.md)

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
- with the caption as `data-caption` and as the thumbnail's `alt`. An album
that does not exist, or has no images yet, renders nothing at all.

A caption may be written as a textfill (`[[/gallery/caption/pass]]`), which is
how one caption serves every language. It is resolved when the gallery is
rendered and escaped on the way into the attribute.

## The panel

**Gallery**, in the Features group, behind `/_admin/gallery/manage`.

The list is one row per album with its shortcode and how many pictures it
holds. A row leads to that album's own screen: what an upload will become
(said before the upload, not after), the file field - several files at once,
uploaded one after the other - and the pictures as a grid, each with its
caption, two buttons to move it, and one to delete it.

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
encoding. A project or a feature that wants webp, a srcset or an imagick
pipeline registers there and renders this feature's images too - without this
feature knowing anything about it, and without a fork of anything. See
[Callbacks](https://github.com/dapeio/nino/blob/main/docs/development.md#callback-reference)
in the developer manual.

## Data

`/data/gallery.php` holds the albums, their names and their captions - the
file the manifest declares, so a backup carries it. The pictures live under
`/images/gallery/<album>/`, where every other uploaded image lives and where a
backup already carries them.

## Styling

The grid reads four custom properties off `.nino-gallery`:
`--nino-gallery-columns` (written by the shortcode), `--nino-gallery-gap` and
`--nino-gallery-radius`. The overlay is the Lightbox feature's and has two of
its own.

## Tests

```bash
NINO_ROOT=../nino php features/Gallery/tests/gallery-smoke.php
```

The manifest and its requirement, the two sizes and the one thing that is
never stored, the render callback carrying this feature's images too, the
panel with its albums, captions, order and deletions, and the markup the
Lightbox reads.

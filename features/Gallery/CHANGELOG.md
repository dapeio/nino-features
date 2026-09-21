# Changelog

All notable changes to the Gallery feature are documented in this file. The
format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), the
versions [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## Unreleased

- Needs Nino `^1.3`, where the constraint said `^1.1`. The sectioned `manual`
  map this manifest carries is only read by a kernel newer than the
  `v1.2.0-beta` tag - `Features::manifest()` refuses it on the tagged one -
  and `^1.1` is satisfied by that kernel, so the catalogue offered the
  feature to an installation that could not then install it. `^1.3` names
  only a kernel that can read the manifest.

### Changed

- **The gallery's markup is a template now.** The list and an item of it were
  strings `Gallery.php` built; they are `templates/gallery.tpl` and
  `gallery-item.tpl`, filled by token with every value escaped before it goes
  in - see AGENTS.md, "Markup belongs in a template". The output is the same.

### Fixed

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

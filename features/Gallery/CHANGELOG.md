# Changelog

All notable changes to the Gallery feature are documented in this file. The
format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), the
versions [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## 1.0.0 — 2026-09-09

First release.

- Albums in `/data/gallery.php`, rendered with `[gallery album="…"]` as a grid
  of thumbnails that open full screen through the Lightbox feature.
- Two derived sizes per upload - a cropped thumbnail and an uncropped large
  view - both through `\Nino\Images`, so a project that registers on
  `\Nino\Images::RENDER` renders these too. The uploaded file is never stored.
- The **Gallery** panel: albums, multi-file upload, captions that may be
  textfills, order, and deletions that take the pictures with them.

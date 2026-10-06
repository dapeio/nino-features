# Changelog

All notable changes to the External Embeds feature are documented in this file.
A release is the tag `embed-<version>` of dapeio/nino-features.

## Unreleased

- Needs Nino `^1.3`, where the constraint said `^1.1`. The sectioned `manual`
  map this manifest carries is only read by a kernel newer than the
  `v1.2.0-beta` tag - `Features::manifest()` refuses it on the tagged one -
  and `^1.1` is satisfied by that kernel, so the catalogue offered the
  feature to an installation that could not then install it. `^1.3` names
  only a kernel that can read the manifest.

### Added

- **It brings its sections of the privacy policy.** The install unit adds
  `embed` (what happens when an embed is released), `embed-youtube` and
  `embed-vimeo` (the provider, `youtube-nocookie.com`, `dnt=1`, where its own
  policy is) to the type `privacy` of Nino 1.4's Legal module - add-only, and a
  Nino without the module ignores the file. A project that uses only one of the
  two providers hides the other section in the Elements panel. It is a starting
  point, not legal advice - see "Legal" in Nino's `docs/development.md`.

### Changed

- **The text keys follow Nino's grammar.** `/embed/load` is
  `/feature/embed/placeholder/button`, `/embed/note` is
  `/feature/embed/placeholder/note`, `/embed/open` is
  `/feature/embed/fallback/link` and `/embed/frame` is
  `/feature/embed/frame/title`. The values are the same. Nothing has been
  published under the old keys, so there is no migration; a project that already
  has texts under them copies the values to the new keys. `nino` stays `^1.3`:
  the keys are the feature's own, and a Nino before 1.4 takes them as they come.

- **`youtube=` and `vimeo=` take the address copied from the browser.** A
  watch address, a short link with its tracking parameter, a share link with a
  time code, `/shorts/`, `/live/` and `/embed/` for YouTube; the page, channel,
  group and showcase addresses and the player address for Vimeo, and the hash of
  an unlisted video (`vimeo.com/<id>/<hash>` or `?h=`). The id is the only thing
  taken from it, and it still has to pass the provider's own pattern, so nothing
  of the address reaches the frame. The host must be the provider's own, compared
  whole: `youtube.com.evil.example` and `www.youtube.com@evil.example` are not
  YouTube; credentials, a port, a playlist, a channel and another provider's
  address name no video. Time codes are dropped. Bare ids work as they did.
  An address that names no video renders nothing, as before, but logs
  `Nino: [embed youtube="…"] is not a video address this can read.` on every
  uncached render (the way Countdown reports a value it cannot read); an empty
  attribute stays silent. The manual and the README say so.

- **`url=` with a YouTube or Vimeo page address becomes the player address.**
  What somebody pastes there is a page that refuses to be framed; it is the same
  no-cookie or `dnt` player `youtube=`/`vimeo=` would build. A player address
  (`/embed/…`, `player.vimeo.com/video/…`) with its own `start` or `autoplay`, and
  every other host, stay as written. A `url=` whose host a policy cannot name -
  credentials, an IP address, a non-ASCII host - renders nothing, since the frame
  would be refused anyway.

- **The manual counted two words where the unit carries four.** The Features
  panel's entry for the install unit said "the two sentences the surface
  carries, into the Text panel", and `install/text/<locale>.php` has four
  fills in it: the two on the surface (`[[/embed/load]]`,
  `[[/embed/note]]`), the `<noscript>` way out (`[[/embed/open]]`) and the
  name the frame is given where the shortcode wrote no `title=`
  (`[[/embed/frame]]`). The README and `embed-smoke.php` both say four; the
  entry says four now, and which they are.

- **The "Asset bundling" note asked a project to do something it does not
  have to.** It said `/features/Embed/assets/...` resolves against the
  project root, and that a project which moved its features elsewhere with
  `NINO_FEATURES_DIR` has to name the two sources under their real path in
  `/nino/html/assets` itself. `\Nino\Filesystem` resolves the virtual
  `/features` prefix against `\Nino\Features::dir()` - a branch of its own,
  older than the `^1.3` this manifest names - so the sources are found after
  a relocation like every other file a feature addresses that way, the two
  templates this feature reads through the same prefix included.

### Fixed

- **An embed's frame was blocked by the browser.** Nino's default
  `Content-Security-Policy` has no `frame-src`, so `default-src 'self'` refused
  the frame of every provider: pressing the surface showed an empty, blocked
  frame. The feature now adds the origin of every `[embed]` on the page to the
  response's `frame-src` (`https://www.youtube-nocookie.com`,
  `https://player.vimeo.com`, the host and port of a `url=`), and the manual
  lists the callback it registers: `/nino/http/output`.

  It stands on the output phase and not on the response phase, because only
  there is the page rendered; the hosts are the ones the shortcodes recorded
  while rendering - a page's texts and elements cannot name a frame host, since
  they cannot carry a shortcode. An existing `frame-src` is extended in place;
  where there is none it is built from `child-src`'s list or else
  `default-src`'s, as a browser would have used it; `'none'` is left as the
  project decided it, and so is a policy with neither fallback. A page with no
  `[embed]` keeps its policy byte for byte. The README's "CSP" section gives the
  reasoning, as Nino's guide asks of any addition to the policy.

  **A page the page cache answers does not carry the added host.** The cache
  keeps the body and answers a hit with the default policy, so the frame is
  blocked there. Until the kernel keeps the widened policy with the entry, list
  the pages that carry embeds under `/nino/cache/blacklist`, or leave the cache
  off. The maintenance page is answered before the output phase and is not
  widened either.

- **An invalid byte in a value rendered as nothing.** `htmlspecialchars()`
  answers input that is not valid UTF-8 with `''` unless `ENT_SUBSTITUTE` is
  among its flags, and every call here spelled the flags out without it.
  Every call carries it now, as the kernel's do, and
  `tests/escaping-smoke.php` reads every feature for the next one.

## 1.0.0 — Unreleased

First release.

- `[embed youtube="…"]`, `[embed vimeo="…"]` and `[embed url="https://…"]` — a
  third-party frame as a surface the visitor presses, with `title`, `poster` and
  `ratio` on the element that is being embedded.
- **The iframe is built, not hidden.** A hidden iframe is still fetched: one
  inside a container with `hidden`, with `display:none` or with
  `visibility:hidden` loads exactly like a visible one, so the pattern of writing
  the frame and hiding it gives the visitor's address to the provider before
  anybody is asked. The address is carried in `data-embed-src` and `embed.js`
  creates the frame when it is released, so there is no request to suppress.
- Two things release one, both the visitor's: a press, and — where the Consent
  feature is installed and the settings name a category — consent they already
  gave. The two features meet over `<html data-consent>` and the `nino:consent`
  event, so neither imports the other and Consent is not a requirement.
- No thumbnail is ever fetched from the provider. A poster is one of the
  project's own images or none; a surface without one is a plain ground with the
  play mark and the host it would talk to.
- The surface is the kernel's own `.nino-video-poster`/`.nino-video-play`, in
  `Nino.css` since 1.0 with nothing driving them until now.

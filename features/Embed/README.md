# External Embeds

A video or a map as a surface the visitor presses — and nothing requested from
the provider until they do.

```
[embed youtube="dQw4w9WgXcQ" title="Wie wir arbeiten"]
[embed vimeo="76979871" title="Der Film" poster="video/still.jpg"]
[embed youtube="https://youtu.be/dQw4w9WgXcQ?si=abc" title="Wie wir arbeiten"]
[embed url="https://www.openstreetmap.org/export/embed.html?bbox=…" title="Anfahrt" ratio="4-3"]
```

## Why this is a feature and not an iframe in a template

Hiding an iframe does not stop it. An iframe inside a container with the `hidden`
attribute, with `display:none`, or with `visibility:hidden` is fetched by the
browser exactly like a visible one — measured in Chromium, all four of these
reach the server:

```html
<div hidden>                     <iframe src="…"></iframe></div>
<div style="display:none">       <iframe src="…"></iframe></div>
<div style="visibility:hidden">  <iframe src="…"></iframe></div>
                                 <iframe src="…"></iframe>
```

So a page that writes the frame and hides it has already given the visitor's IP
address to YouTube or Google before anybody was asked anything. What this
shortcode writes carries the address in `data-embed-src`; `embed.js` builds the
iframe when it is released and not before, so there is no request to suppress in
the first place.

## The two ways one is released

Both are the visitor's:

**A press.** The surface is a real button. Pressing it builds the frame, removes
the button — a hidden button is still in the tab order, and this one has nothing
left to do — and fires `nino:embed` with the host, for anything on the page that
wants to know.

**Consent they already gave.** Where the [Consent](../Consent/README.md) feature
is installed and the **Consent category** setting names one, an embed of that
category is released without a press. `embed.js` reads the allowed list off
`<html data-consent>` and listens for the `nino:consent` event Consent fires when
it changes, so the two features meet over markup rather than over code — neither
imports the other, and a site without Consent simply has no allowed list, which
means every embed waits for a press.

Consent is therefore **not** a requirement of this feature. The press works on
its own, on any project.

## No thumbnail is ever fetched from the provider

A still from a YouTube video lives on a YouTube server. Showing one would make
the very request this exists to prevent, one image earlier — which is why
`[embed]` never builds a provider thumbnail URL, and why a surface with no
picture is a plain ground with the play mark and two sentences on it.

A project that wants a picture there names one of its own:

```
[embed youtube="…" poster="video/still.jpg"]
```

`poster` is a filename below the project's own images, served through
`\Nino\Images`, exactly like an image anywhere else on the site. A name that
climbs out of that directory is left out rather than linked.

## Attributes

| Attribute | What it does |
| --- | --- |
| `youtube="ID"` | the video id, or the address copied from the browser. Goes through `youtube-nocookie.com`, which is Google's own no-cookie host — the same video, and there is no reason to offer the other one |
| `vimeo="ID"` | the numeric video id, or the address copied from the browser. Carries `dnt=1`, Vimeo's own do-not-track flag |
| `url="https://…"` | anything else with an embed address — a map, a booking widget, a calendar. `https` only. A YouTube or Vimeo *page* address here becomes the player address, as under `youtube=`/`vimeo=`; a player address and every other host stay as written |
| `title="…"` | what the surface says and what the loaded frame is called. **Say it for every embed**: it is the only name a screen reader gets |
| `poster="…"` | a picture from the project's own images as the surface |
| `ratio="…"` | `16-9` (default), `4-3`, `1-1` or `21-9` |

Neither `youtube-nocookie.com` nor `dnt=1` makes an embed consent-free — both
still see the visitor's address once the frame is there. They are the better of
two addresses, not a reason to skip the question.

### Pasting an address

`youtube=` and `vimeo=` take what is in the clipboard. Of the address the id is
the one thing kept, so what reaches the frame is still a validated id:

- YouTube: `watch?v=…`, `youtu.be/…`, `/shorts/…`, `/live/…` and `/embed/…`,
  on `youtube.com`, `www.`, `m.` and `youtube-nocookie.com`;
- Vimeo: `vimeo.com/<id>`, `/channels/<name>/<id>`, `/groups/<name>/videos/<id>`,
  `/showcase/<name>/video/<id>` and `player.vimeo.com/video/<id>`. The hash of
  an unlisted video (`vimeo.com/<id>/<hash>`, or `?h=<hash>`) is kept and passed
  on as `?h=`;
- a time code (`t=`, `start=`) and tracking parameters (`si=`, `feature=`) are
  dropped; an address pasted out of an HTML field with `&amp;` still works.

The host has to be one of the provider's own, compared whole: neither
`youtube.com.evil.example` nor `www.youtube.com@evil.example` is YouTube. An
address with credentials or a port, a playlist, a channel and another
provider's address name no video.

An `[embed]` that names no address, or names one that is not one, renders
**nothing at all** — and says so in the log (`Nino: [embed youtube="…"] is not a
video address this can read.`, on every uncached render). A box with no frame
behind it is worse on a page than no box: it is a thing to press that never does
anything. So does a `url=` whose host cannot be named in the policy below:
credentials, an IP address, a non-ASCII host.

## Without JavaScript

The surface is written `hidden` and unhidden by `embed.js`, the way
`[mode-switch]` writes its own. Where the script never runs, what is left is the
`<noscript>` link out to the provider — the only thing that still works. A link
fetches nothing until it is clicked.

## The words

`install/text/<locale>.php` carries four fills, merged into the project's own
`text/<locale>.php` at activation, for every locale it has:

| Fill | English |
| --- | --- |
| `[[/feature/embed/placeholder/button]]` | Load external content |
| `[[/feature/embed/placeholder/note]]` | Pressing this loads content from |
| `[[/feature/embed/fallback/link]]` | Open in a new tab at |
| `[[/feature/embed/frame/title]]` | External content |

`[[/feature/embed/placeholder/note]]` and `[[/feature/embed/fallback/link]]` end where a host name follows — the
shortcode puts it there. From then on they are the project's: an editor changes
them in the Text panel and never opens a feature directory. A key the project
already had is left alone.

## CSP: the hosts a frame is allowed from

Nino's default `Content-Security-Policy` has no `frame-src`, so its
`default-src 'self'` refuses every frame from another host: an embed that was
pressed would show a blocked frame. So this feature names the hosts of the
embeds on the page in the policy's `frame-src`.

On `/nino/http/output`, where the finished page is in hand, it extends the
response's `frame-src` by the origin of every `[embed]` the page rendered:
`https://www.youtube-nocookie.com`, `https://player.vimeo.com`, the host (and
port) of a `url=`. With the shipped policy that gives
`…; frame-src 'self' https://www.youtube-nocookie.com`.

- The hosts are collected when the shortcode renders, not read back out of the
  body. A shortcode is written by the project; the body is every text, element
  and template the page was made of, and a frame host taken from it could be
  anybody's.
- An existing `frame-src` is extended in place, never given a second directive
  (a browser ignores the second). Where there is none it is built from
  `child-src`'s list, or else `default-src`'s — what a browser would have used,
  so a policy that lists more than `'self'` keeps it.
- `'none'` is the project's decision and is left alone, and so is a directive
  that lists no source at all (`frame-src;`), which a browser reads the same
  way. So is a policy with neither fallback, which is unrestricted already.
- A response of `/_admin` or below is never touched: the workbench sends a policy
  of its own.
- A host the policy cannot name safely is refused, and the embed renders
  nothing: credentials, an IP address in any notation - a last label that is a
  number or a hex number (`0x7f000001`) is one -, a non-ascii name, a wildcard.
- A page with no `[embed]` keeps its policy byte for byte.

**Why this is allowed.** Nino's guide says not to add a remote source to the
policy merely to make one widget work. This is the exception: the source is the
origin of a frame the project wrote into its own template, it is added only to
`frame-src`, and without it the feature cannot do what it is for. It does not
add `'unsafe-inline'`, a wildcard or a script source.

**What it does not cover:**

- a page the page cache answers: the cache keeps the body and answers a hit with
  the default policy, so the frame is blocked there. Until the kernel keeps the
  widened policy with the entry, list the pages that carry embeds under
  `/nino/cache/blacklist` (`/page` for one, `/section/*` for a branch), or leave
  the cache off;
- the maintenance page, which is answered before the output phase;
- a provider that redirects its frame to another host: the policy is checked
  against the redirect target too, so that host needs a source of its own;
- a non-ASCII host, which would have to be written as punycode.

## Asset bundling

`embed.css` and `embed.js` are added to the **site's own** bundles —
`/.cache/style.css` and `/.cache/script.js` — the same two the base install's
`html-header.tpl`/`html-footer.tpl` already load on every page, and the same way
the kernel bundles its own `Nino.css`/`Nino.js`.

The sources are addressed as `/features/Embed/assets/…`, which
`\Nino\Filesystem::path()` resolves against `\Nino\Features::dir()` — so they
are found wherever `NINO_FEATURES_DIR` put the features directory, and a
project that moved it has nothing to say in `/nino/html/assets` itself, the
same as for every other feature that ships a static asset.

The surface itself is the kernel's own `.nino-video-poster` and
`.nino-video-play`, which have been in `Nino.css` since 1.0 with nothing driving
them. `embed.css` adds the shapes other than 16:9, the ground a surface with no
picture needs, and the loaded box.

## Settings

| Setting | Default | What it does |
| --- | --- | --- |
| **Consent category** | `external` | the Consent category that releases an embed without a press. Empty: every embed always waits for one |
| **Remember a press for the visit** | off | after one embed of a provider is loaded, load that provider's others on this page too. Nothing is stored — it lasts as long as the page is open, because a decision kept past that would be a decision to declare |

## Privacy policy

`install/elements/privacy.php`, named under `elements` in
`install/manifest.php`, adds three sections (positions 700 to 720) to the type
`privacy` of the Legal module that comes with Nino 1.4 - add-only, as
everything an install unit does: a section an editor changed stays as it is,
one deleted for good does not come back, and a Nino without the module ignores
the file. The text says only what the code does, in German and English; a fact
of the website would be a placeholder of the module
(`#/project/company/contact/email#`), not written text.

- **`embed`** - what every provider does: nothing is loaded before a press or
  before the category "External media" is allowed; then the provider receives
  the visitor's address and the details of the device and the browser, and may
  set cookies; the legal basis is the consent (Art. 6 (1) (a) GDPR, Section 25
  (1) TDDDG) and it can be withdrawn.
- **`embed-youtube`** and **`embed-vimeo`** - the two providers the code knows
  (`Embed::PROVIDERS`): YouTube through `youtube-nocookie.com`, Google Ireland
  Limited, and Vimeo with `dnt=1`, Vimeo.com, Inc. Each names the provider and
  sends the reader to its own privacy policy for what it does with the data
  and on what basis it transfers it to the USA. Whoever uses only one of the
  two hides the other section in the Elements panel; a map or any other
  address embedded with `url=` is covered by the first section alone, and is
  for the operator to name.
- **The category.** The text names "External media", which is the default of
  the setting `category` (**Consent category**, `external`) and the label
  Consent gives it. Whoever
  changes the setting changes the section in the Elements panel too.
- **What was checked.** The cookie-less host and the flag are the ones of
  `Embed::PROVIDERS`. The names, registered offices and links of the providers
  are those of their privacy policies when the section was written, which can
  change: whoever publishes the page checks them.

The section is a starting point and no legal advice, like the texts of Nino's
own Legal module: it is not tailored to any particular website and has not
been legally reviewed, and the operator is responsible for having it checked
and adapted before the website goes live. The notice in full is in the
[Legal](https://github.com/dapeio/nino/blob/main/docs/development.md#legal)
chapter of Nino's `docs/development.md`.

## Data

None. What is embedded is written into the project's own templates, and whether a
visitor allowed it is Consent's cookie in their own browser.

## Tests

`tests/embed-smoke.php` — the manifest, the activation and the four words it
merges, the shortcode over every provider and every way of getting it wrong
(a pasted address in each of its forms, and what is refused), the Content-Security-Policy
(the hosts added, the existing, missing and `'none'` directives, a page with no
embed), the two files it puts into the site's bundles, and deactivation. The one
thing this feature exists for is checked rather than described: what the server
sends holds no iframe and no `src` pointing at the provider. It runs
`tests/embed-js-smoke.js` too where `node` is on the path.

`tests/embed-js-smoke.js` — what `embed.js` does over a DOM stand-in: that there
is no iframe until one is released, which two things release one, what the frame
is then given, that releasing twice builds one frame, and that an address which
is not `https` is refused whatever the markup says.

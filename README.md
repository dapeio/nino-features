# Nino Features

**Language:** English · [Deutsch](README.de.md)

The feature catalogue of [Nino](https://github.com/dapeio/nino): the features a Nino project can install, one per directory below `features/`, each with its own version. The catalogue itself is the list - [catalogue.getnino.dev](https://catalogue.getnino.dev) serves it, and the Features panel of a project reads it.

## Nino 2 in the making

`main` is being rebuilt for Nino 2, which reads components, blocks and themes in a new way. Every feature and application written for Nino 1 waits in `features-1x/` and `applications-1x/`, the tests that read them in `tests-1x/`, until each is moved; `features/`, `applications/` and `tests/` hold what already is Nino 2. No script and no test reads the waiting folders, and `bin/release.sh` refuses to run while `features-1x/` exists: [catalogue.getnino.dev](https://catalogue.getnino.dev) keeps serving the last Nino 1 catalogue, which is the tag `v1-final`. The rest of this document describes the catalogue as it works once the move is done.

## What this catalogue is

A Nino checkout ships no feature. Whatever a project needs beyond the kernel - a newsletter, a search - arrives as a **feature**: a directory with a runtime class, a workbench panel when it has one, an install unit with templates and texts, and a manifest `feature.php` that says what it is, which Nino version it was written for and which settings it offers. What a feature has to deliver is the [Features](https://github.com/dapeio/nino/blob/main/docs/features.md) contract in Nino; every feature here follows it.

`features/<Name>/` is exactly what lands in a project. The repository does nothing over the network: it is a set of files you clone or copy, and the source of what getnino.dev serves as signed archives. What `main` carries is what the catalogue lists, see [A release](#a-release).

A feature is documented in its manifest: the `manual` in `feature.php` is what the Features panel shows on the Description tab, and the `hint` of a setting is what the form says beside it. There is no other documentation to keep. The history of a feature is the commit history.

Beside the features the catalogue publishes two kinds of package that hold no code: a **theme** (`themes/<key>/`: `theme.json`, a part - `part.json` and `part.css` - for the page and for each group of components, blocks of its own, a `preview.webp`) and a **package of blocks** (`blocks/<key>/`: `pack.json`, one folder per block with `block.json`, `block.tpl`, `text/<xx_XX>.json` and `images/<slot>.svg|webp|png|jpg`, a `preview.webp`). They are installed below `private/packages/`, which no web server delivers, by the same function that holds them to the rules at the build: nothing of what a project runs is in them but data. [How they are built and checked](#how-a-package-is-built-and-checked) is below.

Beside the features, the catalogue lists a few **applications** - named sets of features that work together, with a place to start in, see [Write an application](#write-an-application).

## Install a feature

A feature is not installed; it is dropped in and switched on:

1. Copy `features/<Name>/` from this repository into your project's `features/` - as a whole, under the same directory name. The name is the class name: `features/Newsletter/Newsletter.php` is `\Nino\Modules\Newsletter`. A project that reads the catalogue installs from the Features panel instead.
2. Sign in to `/_admin` and open **Features** in the System group. The panel asks for the developer permission `/_admin/features/manage`. It lists every directory with a valid manifest, switched on or not, with its version and with whatever stands in the way of an activation - a Nino version the feature was not written for, a missing PHP extension, a required feature that is not there.
3. **Activate.** That applies the feature's install unit without overwriting anything your project already has, lists the class in `/nino/modules` and records the version under `/nino/features` in `config.php`.

A feature that builds its pages from the components of another says so under `requires` in its manifest, and **Activate** switches the required feature on with it - which has to be in the project's `features/` already. Most features of this catalogue require **Basis**, `features/Basis/`: the header, footer, section, button, image and text components with their groups and blocks, the standard look of a page and the script of its menu. It is a feature like the others: a project that uses one of them has Basis in its `features/`, unless the kernel it runs on still delivers Basis itself - then Basis comes from the kernel and nothing below `features/` is needed (see below).

A panel a feature brings appears with the next load of the workbench, in the rail's Features group. The roles tab of the Users panel offers its permission under that group, and the **Editor** role does not receive it by itself.

**Update:** replace `features/<Name>/` with the new release and press **Update** in the Features panel. The panel offers it as soon as the manifest names a different version than the recorded one; the unit adds what is new and leaves everything the project has edited as it is. **Deactivate** removes the class from `/nino/modules` and nothing else - settings, data, copied templates and texts stay.

**Security:** everything below `features/` is server-side source. The Nino checkout ships `features/.htaccess`, which denies the tree; a web server that does not read `.htaccess` needs the equivalent rule, see [Deployment](https://github.com/dapeio/nino/blob/main/docs/deployment.md) in Nino.

## Develop and test

A feature's tests run against a Nino checkout. Clone Nino beside this repository - `bin/check.sh` expects it at `../nino`, or wherever `NINO_ROOT` points:

```bash
git clone https://github.com/dapeio/nino.git ../nino
bin/check.sh
NINO_ROOT=/path/to/nino bin/check.sh
```

`bin/check.sh` copies every feature the checkout can run into the checkout's `features/` - the same layout a project has -, validates every manifest through `bin/catalogue.php`, runs those features' own tests and removes the copies again; then it runs every test of this repository under `tests/`. A feature written for a newer Nino than the checkout is listed with its reason and left out (`bin/applicable.php` decides).

Basis is a feature like every other one in this repository. A Nino that carries a Basis of its own - as a module, `_nino/Nino/Modules/Basis` - is not one the catalogue runs on: `bin/check.sh`, `bin/applicable.php`, `bin/catalogue.php` and `bin/build.php` end with a sentence that says so, and nothing is copied. `bin/check.sh` marks every folder it copies into `features/` with a file `.check-sh-mark`, takes away only marked folders - at the end, and what a run that was cut off left at the start of the next -, ends with a sentence where a folder of the same name without the mark is there (an empty one is taken away with rmdir), and keeps one run per checkout by a lock in `features/.check-sh.lock`.

A single test runs directly too:

```bash
NINO_ROOT=../nino php features/Hello/tests/hello-smoke.php
```

`bin/catalogue.php` prints what the catalogue would list as JSON - the applications included -, without an archive and without a signature. CI (`.github/workflows/ci.yml`) runs the same tests against Nino's `main` - the catalogue of format 2 is read by Nino 2 alone, and Nino 1 has no packages to read it with -, plus a syntax check, Nino's own contract test, PHPStan and ESLint over `features/`. Nothing waits for it: it is a convenience.

## Write a feature

The manual [Developing a feature](https://github.com/dapeio/nino/blob/main/docs/features.md) in Nino walks a feature from its first file to its test and describes the contract behind it. `features/Hello/` is the complete example of that manual, written to be copied: copy it, then rename the directory, the `key` in `feature.php` and the class `\Nino\Modules\Hello` so the three agree.

```text
features/<Name>/
├── feature.php              the manifest: key, name, description, manual, category, version, nino, requires, settings, data, assets
├── <Name>.php               the runtime class \Nino\Modules\<Name>
├── Admin/Admin.php          the panel, when there is one
├── assets/ install/ text/ templates/   when the feature needs them
└── tests/<key>-smoke.php    a test, when you want one
```

Required are `feature.php` and `<Name>.php`. The manifest's `manual` is the documentation: a `section => handle => line` map - shortcodes, markup, routes, panel, callbacks, install - with the handle as somebody types it and a line in German and English beside it. A test is welcome and never required. The rules that stay, because tests check them, are in [AGENTS.md](AGENTS.md): no markup in PHP, English in code, escaping, the text key grammar, and a section of the privacy policy for a feature that processes personal data. A stylesheet answers to the width in one way: three steps - a phone under 768 px, a tablet from 768 px, a desktop from 1024 px -, four thresholds - 640, 768, 1024 and 1280 px - and always `min-width`, so that a rule is the phone's until a wider step sets it otherwise.

`category` is one of `content`, `ui`, `communication`, `marketing`, `security` or `system`, and no two features carry the same `name` in any locale; `bin/build.php` refuses both.

## Write an application

An **application** is a named set of features that work only together, with a place to start in - a website, a blog, a vCard, later a CRM. The measure is that: it works only as a set and it has an entry point. An addition such as media or statistics stays a single feature, found in the Features panel. The catalogue lists few applications on purpose: a door into a kind of project, not a collection of combinations. The index carries them so that a kernel's setup can offer them; which of them an installation offers is the kernel's decision, as for the features.

An application is one file, `applications/<key>.php`, that returns an array. Every field is written:

```php
return [
	'key'					=> 'website',
	'name'				=> [ 'en_US' => 'Website', 'de_DE' => 'Webseite' ],
	'description'	=> [ 'en_US' => '...', 'de_DE' => '...' ],
	'features'		=> [ 'basis', 'themes', 'builder', 'consent' ],
	'next'				=> '/_admin/#dashboard',
	'start'				=> 'starter',
	'menu'				=> null,
];
```

| Field | What it says |
| --- | --- |
| `key` | a slug, the file name without `.php` |
| `name`, `description` | a text per locale, `de_DE` and `en_US` required; no two applications share a name |
| `features` | the keys of features of this catalogue, at least one, none twice; the kernel sets them up in this order, so a feature follows the features it requires, and those are listed too |
| `next` | the path in the project the setup leads to, with an optional anchor - `/_admin/#builder`, the workbench panel by its uri - or `null`; no scheme, no `//` |
| `start` | who provides the start page: `starter` (the kernel's starter pages), `own` (a feature of the application brings it) or `null` (no statement). Applications with a `start` exclude one another |
| `menu` | `"folded"` where the workbench opens on the `next` panel with its menu folded, or `null` for the menu as it is. The index carries the field only where it is `"folded"`; a kernel that does not know it ignores it |

`bin/build.php` holds every application to this, and one that breaks a rule stops the build with a message naming its file. `bin/catalogue.php` lists them above the features. The index lists the applications by key, except that the one with the most features - `full`, the installation with the features of the catalogue switched on - comes last; a test holds its list to the set of all features but the examples. In `full` the website keeps the start page and the vCard answers at `/vcard`; a kernel that does not yet leave the start page to an application with `start: starter` gives the start page to the card there, so for the start page of the website `full` needs a kernel that does.

## A release

A release is `bin/release.sh`, run by the owner in the checkout on the server. The catalogue is what `main` carries: every run builds every feature into `public/`, the directory the web server delivers, and a feature that did not change gives the same bytes, so nothing a project sees moves.

```bash
git pull
NINO_CATALOGUE_KEY=/safe/place/catalogue-key.pem bin/release.sh
```

It runs `bin/check.sh` first (`--quick` skips it), then `bin/build.php` builds every archive and the picture of every theme and package of blocks into `public/`, writes `catalogue.json` and signs it; every file lands whole, by rename. `NINO_ROOT` names the Nino checkout (default `../nino`), `NINO_CATALOGUE_URL` where `public/` is served from (default `https://catalogue.getnino.dev`). A rehearsal is the build into another directory: `php bin/build.php ../nino /tmp/rehearsal`.

There is one entry per package, the version of its manifest, `theme.json` or `pack.json`, and no tag. A change without a new `version` reaches new installations, not existing ones: the Features panel offers an update from the catalogue only for a higher version. So `version` is raised when installed projects should be offered the update.

## The server

The checkout of this repository, a Nino checkout beside it, php, and a web server that delivers `public/` as static files over https - nginx with a `root` that points at it. A file that is not there answers **404**. No PHP behind the web server, no upload, no configuration beyond the virtual host. The kernel reads the bytes, whatever content type the server names for them. A server elsewhere is given `public/` by whatever moves files to it.

## The signing key

The key pair is made once, offline, and the private half never enters a repository (`.gitignore` refuses `*.pem`):

```bash
openssl ecparam -name prime256v1 -genkey -noout -out catalogue-key.pem
openssl ec -in catalogue-key.pem -pubout -out catalogue-key.pub.pem
```

Keep `catalogue-key.pem` in a safe place outside every repository, with a copy. `catalogue-key.pub.pem` is public: it is what Nino ships as `\Nino\Catalogue::PUBLIC_KEY`, and what a project that reads a catalogue of its own puts under `/nino/catalogue/key` in `config.php`. An empty key accepts no catalogue at all. A new key pair means a new public key in Nino - a catalogue signed with the new key is refused by every kernel that still carries the old one.

## A catalogue of your own

`bin/release.sh` works for any set of features - a fork of this repository - with your own `NINO_CATALOGUE_URL` and a web server that delivers its `public/`. `bin/build.php` alone builds the same files into a directory:

```bash
php bin/build.php ../nino public --base-url https://example.org/features
openssl dgst -sha256 -sign catalogue-key.pem public/catalogue.json | base64 -w0 > public/catalogue.json.sig
```

Without `--key` it writes no signature and prints that one-liner; with `--key catalogue-key.pem` it signs itself and verifies the signature with the public half before it exits. Upload the directory to `https://example.org/features/` and point a project there: `/nino/catalogue/url` names the catalogue's url, `/nino/catalogue/key` its public key, both in `config.php`.

## How a package is built and checked

`bin/build.php` reads `features/<Name>/`, `themes/<key>/` and `blocks/<key>/` - the two latter hold a `.gitkeep` until there is a theme or a package of blocks - through the kernel of a Nino checkout and stops at the first thing the kernel would refuse, with the path of the file and a sentence, before it writes anything:

- every package is held to the rules the kernel installs it by, with the same function - `\Nino\Packages::check()` - over the files of its folder, in the register of the components of every feature of this repository, so that every group a part names is read for what it is. A theme or a package of blocks holds what its kind lists and nothing else: a `.php`, an `.htaccess`, a script or a hidden file is refused by its path; a part's `part.css` is read by the rules of a part (no `@import`, no `url()`, no `!important`, no class or token of another group); a block by the rules of a block (no `[html]`, no markup of its own, only components);
- beyond the kernel: a svg whose rebuild by `\Nino\Images::cleanSvg()` loses an element or an attribute stops the build - what the kernel writes into a project is the rebuild, never the file as it came, and the author is to see what is left of the picture -, a part of a group that no feature of this repository brings stops it, and so does a theme or a package of blocks without its `preview.webp` or whose folder is not its key;
- what an entry says a package needs is computed from the files and never written down: a theme requires `themes`, the feature of every group of its parts and the features whose components its blocks call, and the fonts its brand (`theme.json`, `brand`) suggests, the stacks of the system left out; a package of blocks the features whose components its blocks call; a feature what its manifest requires.

Every archive is a plain tar written by the script, deterministic, and read back with `\Nino\Archive` - the reader the kernel installs with - before it is trusted: it has to hold the files it was built from. The finished document goes through `\Nino\Catalogue::parse()`, and an entry the kernel cannot read is an error of the build. The picture of a theme or a package of blocks lies beside its archive as `<kind>-<key>-<version>.webp`.

## The catalogue, format 2

```json
{ "format": 2, "generated": "2026-11-17T12:00:00Z", "applications": [ { "key": "website", "...": "..." } ], "packages": [ { "kind": "feature", "key": "newsletter", "...": "..." } ] }
```

The kernel reads format 2 and nothing else; an entry of a `kind` it does not know is skipped, and an entry that is not what its kind says is left out and costs nothing but itself.

| Field | Kinds | What it says |
| --- | --- | --- |
| `kind` | all | `feature`, `theme` or `blocks` |
| `key` | all | a slug, unique per kind - `newsletter`, `craft`, `gastro` |
| `version` | all | `major.minor.patch`: that of the manifest, of `theme.json`, of `pack.json` |
| `name`, `description` | all | a string, or a `locale => string` map |
| `category`, `maturity` | feature | the manifest's; `maturity` is left out where it names none |
| `directory` | feature | the one directory the archive holds - `Newsletter`, the class name |
| `requires` | all | `{ "nino": "^2.0", "php": [ ... ], "features": [ ... ], "fonts": [ ... ] }`: the kernel the package was checked against, the PHP extensions of a feature, the keys of the features it requires, the fonts of a theme |
| `parts` | theme | per group of the parts the contract of its `part.json`: `{ "section": { "feature": "basis", "contract": "^1.0" }, "page": { "contract": "kernel@^2.0" } }` |
| `blocks` | theme, blocks | the keys of the blocks of a theme; of a package of blocks the key and the category of each |
| `preview` | theme, blocks | `{ "url", "sha256", "size" }` of the `preview.webp`, at most 256 KB |
| `archive` | all | the https url of the archive, `<kind>-<key>-<version>.tar.gz` - at most 8 MB packed for a feature, 2 MB for a theme or a package of blocks |
| `sha256`, `size` | all | the hex digest of the archive - what the kernel checks a download against - and its size in bytes |
| `released` | all | the day this archive was first published as it is, `YYYY-MM-DD` |

`applications` is a list by key, the application with the most features last, one entry per file of `applications/`, each as the file has it:

```json
{ "key": "website", "name": { "en_US": "Website", "de_DE": "Webseite" }, "description": { "en_US": "...", "de_DE": "..." }, "features": [ "builder", "design", "consent", "forms", "modeswitch", "seo" ], "next": "/_admin/#dashboard", "start": "starter" }
```

An entry carries `menu` only where it is `"folded"`; an application without it has no key for it. The fields are the table of [Write an application](#write-an-application).

`catalogue.json.sig` is its detached signature: ECDSA over SHA-256 of the exact bytes, DER, base64 on one line. `generated` is the time of the last change, ISO 8601 UTC. A `catalogue.json` of another format in the output directory is not built over.

## License

[MIT](LICENSE) - the same author as [Nino](https://github.com/dapeio/nino). The icons of the
features' panels come from [Lucide](https://lucide.dev) (ISC, the ones taken over from Feather also
MIT); their notices are in [THIRD-PARTY-NOTICES.md](THIRD-PARTY-NOTICES.md).

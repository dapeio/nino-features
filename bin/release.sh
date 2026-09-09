#!/bin/sh
# Release one feature version without GitHub - the same steps
# .github/workflows/release.yml takes, run from your own machine:
#
#   1. the feature with that key is found, its feature.php version checked
#      and, where the feature carries a CHANGELOG.md, its entry
#   2. the feature's own tests run against a Nino checkout, where it carries
#      some
#   3. what is published is fetched into dist/ - catalogue.json and, for a
#      re-run, this version's archive - so the release merges into it and
#      never rebuilds a published archive (a 404 is the first release; a 200
#      with an empty body counts as not published, see README.md)
#   4. bin/build.php builds the archive, merges the entry and signs the
#      catalogue with the private key
#   5. one https POST hands catalogue.json, its signature and the archive to
#      server/publish.php - which verifies the signature itself and never
#      overwrites a published archive
#
# Usage: NINO_CATALOGUE_KEY=catalogue-key.pem NINO_CATALOGUE_TOKEN=... bin/release.sh <key> [--dry-run] [--offline] [--strict]
#
#   --dry-run    steps 1 to 4, then stop: nothing is posted, dist/ shows what would be
#   --offline    skip step 3: use what dist/ already holds (a copy of the
#                published files you fetched yourself, or nothing for a first
#                release on a server this machine cannot reach)
#   --strict     require a CHANGELOG.md entry, a README.md and a test, the
#                way the catalogue may later insist on them (also
#                NINO_RELEASE_STRICT=1)
#
# Environment:
#   NINO_FEAT_ROOT  			the Features checkout
#   NINO_ROOT       			the Nino checkout to test against (default ../nino)
#   NINO_CATALOGUE_URL    where the catalogue is served from (default https://catalogue.getnino.dev)
#   NINO_PUBLISH_URL      the endpoint (default $NINO_CATALOGUE_URL/publish.php)
#   NINO_CATALOGUE_KEY    the private PEM key that signs catalogue.json (required)
#   NINO_CATALOGUE_TOKEN  the token server/publish.php is configured with (required unless --dry-run)
#   NINO_CATALOGUE_DIR    the working directory (default ./dist)
#   NINO_RELEASE_STRICT   set to 1 for the same effect as --strict
set -e

here="${NINO_FEAT_ROOT:-$(cd -- "$(dirname -- "$0")/.." && pwd)}"
root=${NINO_ROOT:-$here/../nino}
base=${NINO_CATALOGUE_URL:-https://catalogue.getnino.dev}
base=${base%/}
endpoint=${NINO_PUBLISH_URL:-$base/publish.php}
dist=${NINO_CATALOGUE_DIR:-$here/dist}

key=""
dry=0
offline=0
strict=${NINO_RELEASE_STRICT:-0}
for arg in "$@"; do
	case "$arg" in
		--dry-run) dry=1 ;;
		--offline) offline=1 ;;
		--strict) strict=1 ;;
		--*) echo "Unknown option $arg" >&2; exit 2 ;;
		*) key=$arg ;;
	esac
done
[ "$strict" = 1 ] || strict=0

if [ -z "$key" ]; then
	echo "Usage: NINO_CATALOGUE_KEY=catalogue-key.pem NINO_CATALOGUE_TOKEN=... bin/release.sh <key> [--dry-run] [--offline] [--strict]" >&2
	exit 2
fi

if ! printf '%s\n' "$key" | grep -Eq '^[a-z][a-z0-9-]*$'; then
	echo "\"$key\" is not a feature key (a slug such as newsletter)" >&2
	exit 2
fi

if [ ! -f "$root/_nino/Nino.php" ]; then
	echo "No Nino checkout at $root - clone https://github.com/dapeio/nino beside this repository or set NINO_ROOT" >&2
	exit 2
fi

if [ -z "$NINO_CATALOGUE_KEY" ] || [ ! -f "$NINO_CATALOGUE_KEY" ]; then
	echo "NINO_CATALOGUE_KEY has to name the private PEM key that signs catalogue.json" >&2
	exit 2
fi

if [ "$dry" = 0 ] && [ -z "$NINO_CATALOGUE_TOKEN" ]; then
	echo "NINO_CATALOGUE_TOKEN has to hold the token server/publish.php is configured with (or pass --dry-run)" >&2
	exit 2
fi

# --- 1. the feature: directory and version from the manifests, the changelog entry

preview=$(php "$here/bin/catalogue.php" "$root")
directory=$(printf '%s' "$preview" | php -r '$d = json_decode( stream_get_contents( STDIN ), true ); foreach( $d["features"] ?? [] as $f ) if( $f["key"] === $argv[1] ) { echo $f["directory"]; exit; }' "$key")
version=$(printf '%s' "$preview" | php -r '$d = json_decode( stream_get_contents( STDIN ), true ); foreach( $d["features"] ?? [] as $f ) if( $f["key"] === $argv[1] ) { echo $f["version"]; exit; }' "$key")

if [ -z "$directory" ] || [ -z "$version" ]; then
	echo "No feature below features/ has the key \"$key\"" >&2
	exit 1
fi

escaped=$(printf '%s' "$version" | sed 's/[.]/\\./g')
if [ -f "$here/features/$directory/CHANGELOG.md" ] && grep -Eq "^## $escaped( |$)" "$here/features/$directory/CHANGELOG.md"; then
	: # the entry is there
elif [ "$strict" = 1 ]; then
	echo "features/$directory/CHANGELOG.md has no entry \"## $version\" - a release carries its changelog entry (strict)" >&2
	exit 1
else
	echo "note: features/$directory has no CHANGELOG.md entry \"## $version\" - fine, the catalogue does not require one"
fi

if [ "$strict" = 1 ] && [ ! -f "$here/features/$directory/README.md" ]; then
	echo "features/$directory has no README.md - a feature is published with one (strict)" >&2
	exit 1
fi

echo "Releasing $key $version from features/$directory"

# --- 2. the feature's own tests, the way bin/check.sh runs them

# A copy the checkout already carries (bin/check.sh places one for its
# run, a project has its own) is used as it is and left alone
if [ -e "$root/features/$directory" ]; then
	echo "features/$directory already exists in $root - testing that copy"
else
	cp -R "$here/features/$directory" "$root/features/$directory"
	trap 'rm -rf "$root/features/$directory"' EXIT
fi

found=0
for test in "$root/features/$directory"/tests/*-smoke.php; do
	[ -e "$test" ] || continue
	found=1
	NINO_ROOT="$root" php "$test"
done
if [ "$found" = 0 ]; then
	if [ "$strict" = 1 ]; then
		echo "features/$directory carries no tests/*-smoke.php - a feature is published with its test (strict)" >&2
		exit 1
	else
		echo "note: features/$directory carries no tests/*-smoke.php - fine, the catalogue does not require one"
	fi
fi

# --- 3. what is published

mkdir -p "$dist"
archive="$key-$version.tar.gz"

if [ "$offline" = 0 ]; then
	for file in catalogue.json "$archive"; do
		code=$(curl -sS -o "$dist/$file" -w '%{http_code}' "$base/$file") || code=000
		case "$code" in
			200)
				if [ -s "$dist/$file" ]; then
					echo "$file is published - fetched"
				else
					echo "warning: $base/$file answered 200 with an empty body - treated as not published; a missing file should be a 404, check the server" >&2
					rm -f "$dist/$file"
				fi ;;
			404) echo "$file is not published yet"; rm -f "$dist/$file" ;;
			*) echo "$base/$file answered $code" >&2; exit 1 ;;
		esac
	done
else
	echo "offline: using what $dist holds"
fi

# --- 4. build, merge, sign

php "$here/bin/build.php" "$root" "$dist" --base-url "$base" --only "$key" --key "$NINO_CATALOGUE_KEY"

test -s "$dist/$archive"
test -s "$dist/catalogue.json"
test -s "$dist/catalogue.json.sig"

if [ "$dry" = 1 ]; then
	echo "dry run: nothing posted - $dist holds catalogue.json, catalogue.json.sig and $archive"
	exit 0
fi

# --- 5. publish

response=$(mktemp)
code=$(curl -sS -o "$response" -w '%{http_code}' \
	-H "X-Publish-Token: $NINO_CATALOGUE_TOKEN" \
	-F "catalogue=@$dist/catalogue.json;type=application/json" \
	-F "signature=@$dist/catalogue.json.sig;type=text/plain" \
	-F "archives[]=@$dist/$archive;type=application/gzip" \
	"$endpoint") || code=000

cat "$response"; echo
rm -f "$response"

if [ "$code" != 200 ]; then
	echo "$endpoint answered $code" >&2
	exit 1
fi

echo "Published $key $version"

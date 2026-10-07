#!/bin/sh
# bin/release.sh - make the catalogue what main carries, and put it on the server.
# Usage: NINO_CATALOGUE_KEY=key.pem NINO_CATALOGUE_TARGET=user@host:/dir/ bin/release.sh [--dry-run] [--quick]
#   --dry-run   build into public/, upload nothing
#   --quick     skip bin/check.sh (the tests ran already)
#
# Environment:
#   NINO_ROOT              the Nino checkout to test and build against (default ../nino)
#   NINO_CATALOGUE_URL     where the catalogue is served from (default https://catalogue.getnino.dev)
#   NINO_CATALOGUE_KEY     the private PEM key that signs catalogue.json (required)
#   NINO_CATALOGUE_TARGET  the directory the catalogue is served from, as rsync names it (required)
#
# public/ is the local mirror of the server. The published catalogue comes down
# into it first, because the build reads it - an entry keeps its release day
# while its archive is the same bytes - and what goes back up is public/ exactly,
# so an archive the catalogue no longer names is removed from the server.
set -e

here=$(cd "$(dirname "$0")/.." && pwd)
root=${NINO_ROOT:-$here/../nino}
url=${NINO_CATALOGUE_URL:-https://catalogue.getnino.dev}
key=${NINO_CATALOGUE_KEY:?the PEM private key that signs catalogue.json}
target=${NINO_CATALOGUE_TARGET:?where the catalogue is served from, as rsync names it: user@host:/dir/}
case $target in *:) echo "NINO_CATALOGUE_TARGET names a host, not a directory: add the directory after the colon" >&2; exit 2 ;; esac
target=${target%/}/
pub=$here/public

dry=0
quick=0
for arg in "$@"; do
	case "$arg" in
		--dry-run) dry=1 ;;
		--quick) quick=1 ;;
		*) echo "Unknown option $arg" >&2; exit 2 ;;
	esac
done

# 1. the gate: every manifest, every test
[ "$quick" = 1 ] || NINO_ROOT="$root" "$here/bin/check.sh"

# 2. what is published - the build reads it
mkdir -p "$pub"
rsync -a --delete "$target" "$pub/"

# 3. every archive, the catalogue, the signature
php "$here/bin/build.php" "$root" "$pub" --base-url "$url" --key "$key"

[ "$dry" = 0 ] || { echo "dry run - public/ shows what would go out, nothing was uploaded"; exit 0; }

# 4. up again: the server gets exactly what public/ holds
rsync -a --delete --chmod=D755,F644 "$pub/" "$target"
echo "published to $target"

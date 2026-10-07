#!/bin/sh
# bin/release.sh - make public/ what main carries. The repository lives on the
# server and public/ is the directory the web server delivers as the catalogue,
# so a release is a build into it: every archive, catalogue.json and its
# signature. An archive the catalogue no longer names is removed. Nothing is
# copied anywhere; a web server elsewhere is given public/ by whatever moves
# files to it.
#
# Usage: NINO_CATALOGUE_KEY=key.pem bin/release.sh [--quick]
#   --quick     skip bin/check.sh (the tests ran already)
#
# Environment:
#   NINO_ROOT            the Nino checkout to test and build against (default ../nino)
#   NINO_CATALOGUE_URL   where public/ is served from (default https://catalogue.getnino.dev)
#   NINO_CATALOGUE_KEY   the private PEM key that signs catalogue.json (required)
#
# A rehearsal is bin/build.php with another directory:
#   php bin/build.php ../nino /tmp/rehearsal --key key.pem
set -e

here=$(cd "$(dirname "$0")/.." && pwd)
root=${NINO_ROOT:-$here/../nino}
url=${NINO_CATALOGUE_URL:-https://catalogue.getnino.dev}
key=${NINO_CATALOGUE_KEY:?the PEM private key that signs catalogue.json}
pub=$here/public

quick=0
for arg in "$@"; do
	case "$arg" in
		--quick) quick=1 ;;
		*) echo "Unknown option $arg" >&2; exit 2 ;;
	esac
done

# 1. the gate: every manifest, every test
[ "$quick" = 1 ] || NINO_ROOT="$root" "$here/bin/check.sh"

# 2. every archive, the catalogue, the signature - into the served directory
mkdir -p "$pub"
php "$here/bin/build.php" "$root" "$pub" --base-url "$url" --key "$key"
echo "released: $pub is what main carries"

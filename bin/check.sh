#!/bin/sh
# Run every feature's own tests against a Nino checkout - the one beside this
# repository (../nino), or the one NINO_ROOT names. The features are copied
# into the checkout's features/ for the run, the same layout a project has,
# and removed again afterwards. Then the publishing tool's own test,
# tests/build-smoke.php, builds a signed catalogue into a directory of its
# own against the same checkout, and tests/publish-smoke.php drives the
# endpoint a release is posted to.
#
# Usage: bin/check.sh            (../nino)
#        NINO_ROOT=/path/to/nino bin/check.sh
set -e

here=$(cd "$(dirname "$0")/.." && pwd)
root=${NINO_ROOT:-$here/../nino}

if [ ! -f "$root/_nino/Nino.php" ]; then
	echo "No Nino checkout at $root - clone https://github.com/dapeio/nino beside this repository or set NINO_ROOT" >&2
	exit 2
fi

if [ ! -f "$root/_nino/Nino/Features/Features.php" ] || [ ! -f "$root/tests/harness.php" ]; then
	echo "The Nino checkout at $root predates the feature contract (no \\Nino\\Features, no tests/harness.php) - the catalogue needs a Nino that carries it" >&2
	exit 2
fi

installed=""
for dir in "$here"/features/*/; do
	name=$(basename "$dir")
	if [ -e "$root/features/$name" ]; then
		echo "features/$name already exists in $root - not touching it" >&2
		continue
	fi
	cp -R "$dir" "$root/features/$name"
	installed="$installed $name"
done

cleanup() {
	for name in $installed; do
		rm -rf "$root/features/$name"
	done
}
trap cleanup EXIT

php "$here/bin/catalogue.php" "$root" > /dev/null
for test in "$root"/features/*/tests/*-smoke.php; do
	[ -e "$test" ] || continue
	php "$test"
done

NINO_ROOT="$root" php "$here/tests/build-smoke.php"
NINO_ROOT="$root" php "$here/tests/publish-smoke.php"
NINO_ROOT="$root" php "$here/tests/release-smoke.php"

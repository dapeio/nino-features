#!/bin/sh
# Run every feature's own tests against a Nino checkout - the one beside this
# repository (../nino), or the one NINO_ROOT names. The features the checkout
# can run - bin/applicable.php names them by their "nino" constraint - are
# copied into the checkout's features/ for the run, the same layout a project
# has, and removed again afterwards; the others are listed with their reason.
# Then every test of this repository's own under tests/, against the same
# checkout - each test's header says what it holds.
#
# features-1x/, applications-1x/ and tests-1x/ are not read: they hold what was
# written for Nino 1 and waits for its move to Nino 2 (see README.md).
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

applicable=$(php "$here/bin/applicable.php" "$root")

installed=""
for name in $applicable; do
	dir="$here/features/$name"
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

for test in "$here"/tests/*-smoke.php; do
	[ -e "$test" ] || continue
	NINO_ROOT="$root" php "$test"
done

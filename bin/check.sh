#!/bin/sh
# Run every feature's own tests against a Nino checkout - the one beside this
# repository (../nino), or the one NINO_ROOT names. The features the checkout
# can run - bin/applicable.php names them by their "nino" constraint - are
# copied into the checkout's features/ for the run, the same layout a project
# has, and removed again afterwards; the others are listed with their reason.
# Then every test of this repository's own under tests/, against the same
# checkout - the lines at the end of this script are the list, and each test's
# header says what it holds.
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

NINO_ROOT="$root" php "$here/tests/keys-smoke.php"
NINO_ROOT="$root" php "$here/tests/legal-smoke.php"
php "$here/tests/markup-smoke.php"
php "$here/tests/language-smoke.php"
php "$here/tests/escaping-smoke.php"
php "$here/tests/panels-smoke.php"
NINO_ROOT="$root" php "$here/tests/demo-catalogue-smoke.php"
NINO_ROOT="$root" php "$here/tests/build-smoke.php"
NINO_ROOT="$root" php "$here/tests/release-smoke.php"

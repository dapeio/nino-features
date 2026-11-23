#!/bin/sh
# Run every feature's own tests against a Nino checkout - the one beside this
# repository (../nino), or the one NINO_ROOT names. The features the checkout
# can run - bin/applicable.php names them by their "nino" constraint - are
# copied into the checkout's features/ for the run, the same layout a project
# has, and removed again afterwards; the others are listed with their reason.
# A feature the kernel delivers itself (bin/delivered.php: Basis, as long as the
# kernel carries it as a module) is not copied - the kernel refuses a directory of
# that name -, and only its tests/ go in, into features/.delivered-<Name>/ (a
# directory the checkout's own suites do not read as a feature), so that they run
# against the one the kernel has; the line "delivered by Nino ... itself" in the
# output says so.
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

# What this run puts below the checkout's features/ is written to a list there, one name a line, before it is put: a run that is cut off - Ctrl-C, a kill, a
# crash - leaves the list behind, and the next run takes away what it names and nothing else, never a feature the checkout had before. The directories for the
# tests of a delivered feature are named by this script alone (.delivered-*), so whatever is left of those goes as well
marker="$root/features/.check-sh"

cleanup() {
	if [ -f "$marker" ]; then
		while read -r name; do
			case "$name" in
				''|.|..|*/*) continue ;;
			esac
			rm -rf "$root/features/$name"
		done < "$marker"
		rm -f "$marker"
	fi
}

cleanup
for left in "$root"/features/.delivered-*; do
	[ -e "$left" ] && rm -rf "$left"
done

# A signal ends the script through exit, which runs the cleanup
trap cleanup EXIT
trap 'exit 130' INT
trap 'exit 143' TERM
trap 'exit 129' HUP

applicable=$(php "$here/bin/applicable.php" "$root")

for name in $applicable; do
	dir="$here/features/$name"
	if [ -e "$root/features/$name" ]; then
		echo "features/$name already exists in $root - not touching it" >&2
		continue
	fi
	echo "$name" >> "$marker"
	cp -R "$dir" "$root/features/$name"
done

for name in $(php "$here/bin/applicable.php" "$root" --delivered); do
	[ -d "$here/features/$name/tests" ] || continue
	# A directory without a manifest below features/ is one the checkout's own suites refuse, so the tests go into a hidden one beside the features
	echo ".delivered-$name" >> "$marker"
	mkdir "$root/features/.delivered-$name"
	cp -R "$here/features/$name/tests" "$root/features/.delivered-$name/tests"
	echo "features/$name: delivered by the kernel - its tests run against that one"
done

php "$here/bin/catalogue.php" "$root" > /dev/null
for test in "$root"/features/*/tests/*-smoke.php "$root"/features/.delivered-*/tests/*-smoke.php; do
	[ -e "$test" ] || continue
	php "$test"
done

for test in "$here"/tests/*-smoke.php; do
	[ -e "$test" ] || continue
	NINO_ROOT="$root" php "$test"
done

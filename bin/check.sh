#!/bin/sh
# Run every feature's own tests against a Nino checkout - the one beside this
# repository (../nino), or the one NINO_ROOT names. The features the checkout
# can run - bin/applicable.php names them by their "nino" constraint - are
# copied into the checkout's features/ for the run, the same layout a project
# has, and removed again afterwards; the others are listed with their reason.
# Basis is a feature like the others: a Nino that carries a Basis of its own
# (_nino/Nino/Modules/Basis) is turned away with a sentence before anything is
# copied.
# Then every test of this repository's own under tests/, against the same
# checkout - each test's header says what it holds.
#
# What the run puts below features/ is told apart by a mark: every folder it copies
# holds the file .check-sh-mark, written by this script before the first file of
# the feature is copied, and only a folder with that mark is ever taken away -
# at the end, and at the start of the next run for what a run that was cut off
# (kill -9, a power cut) left. A folder of the same name that is there without the
# mark is the checkout's own: the run ends with a sentence and leaves it - unless it
# is empty, which is what a run leaves that was cut off between making the folder and
# marking it, and rmdir takes that - and features/.htaccess and everything outside
# features/ is left in any case.
# One run at a time per checkout: features/.check-sh.lock holds the number of the
# process that runs; a second run says so, names the process and says the lock can be taken
# away by hand if that is no run of check.sh, and ends with 2; a lock whose process
# is gone is taken over, and a lock that is no readable file ends the run with a sentence.
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

if [ -d "$root/_nino/Nino/Modules/Basis" ]; then
	echo "The Nino checkout at $root carries a Basis of its own (_nino/Nino/Modules/Basis): this catalogue needs a Nino without a Basis of its own, because Basis is a feature here" >&2
	exit 2
fi

if [ ! -d "$root/features" ]; then
	echo "The Nino checkout at $root has no features/ directory" >&2
	exit 2
fi

mark=".check-sh-mark"
markText="written by bin/check.sh of the Nino feature catalogue: this folder was copied for a run and goes with it"
lock="$root/features/.check-sh.lock"
locked=no

# Every folder below features/ that carries the mark goes, and only those
cleanup() {
	for folder in "$root"/features/*/ "$root"/features/.[!.]*/; do
		[ -d "$folder" ] || continue
		[ -L "${folder%/}" ] && continue
		if [ -f "$folder$mark" ] && [ "$(cat "$folder$mark" 2>/dev/null)" = "$markText" ]; then
			rm -rf "${folder%/}"
		fi
	done
}

release() {
	if [ "$locked" = yes ]; then
		cleanup
		if [ "$(cat "$lock" 2>/dev/null)" = "$$" ]; then
			rm -f "$lock"
		fi
	fi
}

# A signal ends the script through exit, which runs the release
trap release EXIT
trap 'exit 130' INT
trap 'exit 143' TERM
trap 'exit 129' HUP

# The lock is made in one step (a file that is not there yet) and holds the number of the process
lockProblem() {
	echo "The lock $lock is not a readable file ($1) - take it away by hand once no run of bin/check.sh is going against $root" >&2
	exit 2
}

tries=0
while ! ( set -C; echo "$$" > "$lock" ) 2>/dev/null; do
	if [ -d "$lock" ]; then
		lockProblem "it is a folder"
	elif [ -L "$lock" ] && [ ! -e "$lock" ]; then
		lockProblem "it is a link that leads nowhere"
	elif [ -e "$lock" ] && { [ ! -f "$lock" ] || [ ! -r "$lock" ]; }; then
		lockProblem "it cannot be read"
	fi
	holder=$(cat "$lock" 2>/dev/null || true)
	case "$holder" in
		''|*[!0-9]*) alive=no ;;
		*) if kill -0 "$holder" 2>/dev/null; then alive=yes; else alive=no; fi ;;
	esac
	if [ "$alive" = yes ]; then
		echo "bin/check.sh may be running against $root: the lock features/.check-sh.lock names process $holder, which is alive - wait for it; if process $holder is no run of check.sh, take the lock away by hand" >&2
		exit 2
	fi
	tries=$((tries + 1))
	if [ "$tries" -gt 3 ]; then
		echo "The lock $lock could not be taken over - remove it by hand once no run is going" >&2
		exit 2
	fi
	rm -f "$lock" 2>/dev/null || lockProblem "it cannot be removed"
done
locked=yes

# What a run that was cut off left behind
cleanup

applicable=$(php "$here/bin/applicable.php" "$root")

# A folder of the same name that the checkout had before is not this script's to touch: the run ends before a feature is copied
for name in $applicable; do
	target="$root/features/$name"
	if [ -e "$target" ] || [ -L "$target" ]; then
		# An empty folder without the mark is what a run leaves that was cut off between making the folder and marking it: rmdir takes nothing that holds anything
		if [ -d "$target" ] && [ ! -L "$target" ] && rmdir "$target" 2>/dev/null; then
			echo "features/$name was an empty folder without the mark (left by a run that was cut off): taken away with rmdir" >&2
			continue
		fi
		echo "features/$name is there without the mark of bin/check.sh in $root - it is not a copy of an earlier run, so it is left as it is and nothing is copied; take it away by hand or use another checkout" >&2
		exit 2
	fi
done

for name in $applicable; do
	mkdir "$root/features/$name"
	echo "$markText" > "$root/features/$name/$mark"
	cp -R "$here/features/$name/." "$root/features/$name/"
done

php "$here/bin/catalogue.php" "$root" > /dev/null
for test in "$root"/features/*/tests/*-smoke.php; do
	[ -e "$test" ] || continue
	php "$test"
done

for test in "$here"/tests/*-smoke.php; do
	[ -e "$test" ] || continue
	NINO_ROOT="$root" php "$test"
done

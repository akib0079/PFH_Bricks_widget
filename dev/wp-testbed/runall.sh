#!/bin/bash
# Run every testbed suite over HTTP; print failures and a total.
#
#   bash dev/wp-testbed/runall.sh /path/to/testbed     (the folder holding wp/)
#
# The testbed is the one 00-bootstrap.sh stands up, served by php -S on
# $PORT. The plugin and the suites are copied in first, so the run always
# tests the working tree.
TB="${1:-${PFH_TESTBED:?usage: runall.sh <testbed folder>}}/wp"
PORT="${PORT:-8913}"
SRC="$(cd "$(dirname "$0")" && pwd)"
PLUGIN="$(cd "$SRC/../../pfh-bricks-widgets" && pwd)"

rsync -a --delete --exclude assets/img "$PLUGIN/" "$TB/wp-content/plugins/pfh-bricks-widgets/"
cp "$SRC/mu-bricks-stub.php" "$TB/wp-content/mu-plugins/"
cp "$SRC"/test-*.php "$SRC"/fixture-*.php "$SRC"/reset-fixture.php "$TB/"
cd "$TB" || exit 1

suites=0; passed=0; bad=0
for f in test-*.php; do
	case "$f" in test-zip.php|test-no-bricks.php) continue;; esac
	out=$(curl -s --max-time 120 "http://127.0.0.1:$PORT/$f")
	last=$(echo "$out" | sed 's/\x1b\[[0-9;]*m//g' | grep -E "[0-9]+ passed, [0-9]+ failed" | tail -1)
	suites=$((suites+1))
	p=$(echo "$last" | grep -oE "^[^0-9]*[0-9]+" | grep -oE "[0-9]+$")
	fl=$(echo "$last" | grep -oE "[0-9]+ failed" | grep -oE "[0-9]+")
	passed=$((passed+${p:-0}))
	if [ -z "$last" ] || [ "${fl:-1}" != "0" ]; then
		bad=$((bad+1))
		echo "== $f: ${last:-NO RESULT}"
		echo "$out" | sed 's/\x1b\[[0-9;]*m//g' | grep -E "FAIL|Fatal|Warning|Notice|Error" | head -15
	fi
done
echo "suites $suites, passed $passed, suites with problems $bad"

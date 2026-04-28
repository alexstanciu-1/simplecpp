#!/usr/bin/env bash
set -euo pipefail

project_dir="$(cd "$(dirname "$0")/.." && pwd)"
repo_root="$(cd "$project_dir/../../../.." && pwd)"
scpp_cmd=(php "$repo_root/bin/scpp.php")
manifest_path="$project_dir/tests/examples_manifest.txt"
binary_path="$project_dir/.prism/build/main"

cd "$project_dir"

"${scpp_cmd[@]}" build >/tmp/php_examples_build.stdout 2>/tmp/php_examples_build.stderr || {
	cat /tmp/php_examples_build.stdout
	cat /tmp/php_examples_build.stderr >&2
	exit 1
}

if [[ ! -x "$binary_path" ]]; then
	echo "Expected built binary at $binary_path" >&2
	exit 1
fi

while IFS= read -r example_id; do
	[[ -z "$example_id" ]] && continue
	expected_path="$project_dir/expected/${example_id}.stdout"
	if [[ ! -f "$expected_path" ]]; then
		echo "Missing expected output for $example_id" >&2
		exit 1
	fi
done < "$manifest_path"

suite_stdout_path="$(mktemp)"
suite_stderr_path="$(mktemp)"

if ! "$binary_path" >"$suite_stdout_path" 2>"$suite_stderr_path"; then
	echo "Suite executable failed" >&2
	cat "$suite_stderr_path" >&2
	rm -f "$suite_stdout_path" "$suite_stderr_path"
	exit 1
fi

if [[ -s "$suite_stderr_path" ]]; then
	echo "Unexpected stderr from suite executable" >&2
	cat "$suite_stderr_path" >&2
	rm -f "$suite_stdout_path" "$suite_stderr_path"
	exit 1
fi

python3 - "$manifest_path" "$project_dir/expected" "$suite_stdout_path" <<'PY'
import sys
from pathlib import Path

manifest_path = Path(sys.argv[1])
expected_dir = Path(sys.argv[2])
suite_stdout_path = Path(sys.argv[3])

example_ids = [line.strip() for line in manifest_path.read_text().splitlines() if line.strip()]
lines = suite_stdout_path.read_text().splitlines(keepends=True)
sections = {}
current = None

for line in lines:
	if line.startswith("== ") and line.rstrip("\n").endswith(" =="):
		current = line.strip()[3:-3]
		sections[current] = []
		continue
	if current is not None:
		sections[current].append(line)

for example_id in example_ids:
	expected_path = expected_dir / f"{example_id}.stdout"
	expected = expected_path.read_text()
	actual = "".join(sections.get(example_id, []))
	if actual != expected:
		print(f"Output mismatch for {example_id}", file=sys.stderr)
		print(f"Expected from {expected_path}:", file=sys.stderr)
		print(expected, file=sys.stderr, end="" if expected.endswith("\n") or expected == "" else "\n")
		print("Actual:", file=sys.stderr)
		print(actual, file=sys.stderr, end="" if actual.endswith("\n") or actual == "" else "\n")
		sys.exit(1)
	print(f"ok {example_id}")
PY

rm -f "$suite_stdout_path" "$suite_stderr_path"

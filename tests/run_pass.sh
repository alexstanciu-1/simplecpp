#!/usr/bin/env bash
set -euo pipefail

root_dir="$(cd "$(dirname "$0")/.." && pwd)"
build_dir="$root_dir/.test_build/pass"

rm -rf "$build_dir"
mkdir -p "$build_dir"

failed=0

for test_file in "$root_dir"/tests/pass/*.cpp; do
	test_name="$(basename "$test_file" .cpp)"
	output_bin="$build_dir/$test_name"

	echo "==> compiling pass test: $test_name"
	g++ -std=c++20 -Wall -Wextra -Werror -I"$root_dir/include" "$root_dir"/src/*.cpp "$test_file" -o "$output_bin"

	echo "==> running pass test: $test_name"
	if ! "$output_bin"; then
		echo "PASS TEST FAILED AT RUNTIME: $test_name"
		failed=1
	fi
done

if [[ "$failed" -ne 0 ]]; then
	exit 1
fi

echo "All pass tests succeeded."

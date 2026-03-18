#!/usr/bin/env bash
set -euo pipefail

root_dir="$(cd "$(dirname "$0")/.." && pwd)"
build_dir="$root_dir/.test_build/fail_compile"

rm -rf "$build_dir"
mkdir -p "$build_dir"

failed=0

for test_file in "$root_dir"/tests/fail_compile/*.cpp; do
	test_name="$(basename "$test_file" .cpp)"
	log_file="$build_dir/$test_name.log"

	echo "==> compiling fail test: $test_name"
	if g++ -std=c++20 -Wall -Wextra -Werror -I"$root_dir/include" "$root_dir"/src/*.cpp "$test_file" -o "$build_dir/$test_name" >"$log_file" 2>&1; then
		echo "FAIL-COMPILE TEST UNEXPECTEDLY SUCCEEDED: $test_name"
		failed=1
	else
		echo "expected compile failure observed: $test_name"
	fi
done

if [[ "$failed" -ne 0 ]]; then
	exit 1
fi

echo "All fail-compile tests produced compile errors as expected."

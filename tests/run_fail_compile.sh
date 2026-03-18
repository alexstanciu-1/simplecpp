#!/usr/bin/env bash
set -euo pipefail

root_dir="$(cd "$(dirname "$0")/.." && pwd)"
build_dir="$root_dir/.test_build/fail_compile"

rm -rf "$build_dir"
mkdir -p "$build_dir"

failed=0

while IFS= read -r test_file; do
	test_name="$(basename "$test_file" .cpp)"
	group_name="$(basename "$(dirname "$test_file")")"
	parent_name="$(basename "$(dirname "$(dirname "$test_file")")")"
	log_file="$build_dir/${parent_name}_${group_name}_${test_name}.log"

	echo "==> compiling fail test: ${parent_name}/${group_name}/${test_name}"
	if g++ -std=c++20 -Wall -Wextra -Werror -I"$root_dir/include" "$root_dir"/src/*.cpp "$test_file" -o "$build_dir/${parent_name}_${group_name}_${test_name}" >"$log_file" 2>&1; then
		echo "FAIL-COMPILE TEST UNEXPECTEDLY SUCCEEDED: ${parent_name}/${group_name}/${test_name}"
		failed=1
	else
		echo "expected compile failure observed: ${parent_name}/${group_name}/${test_name}"
	fi
done < <(find "$root_dir/tests/language_surface/fail_compile" "$root_dir/tests/runtime_mechanics/fail_compile" -name '*.cpp' | sort)

if [[ "$failed" -ne 0 ]]; then
	exit 1
fi

echo "All fail-compile tests produced compile errors as expected."

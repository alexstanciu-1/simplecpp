#!/usr/bin/env bash
set -euo pipefail

root_dir="$(cd "$(dirname "$0")/.." && pwd)"
build_dir="$root_dir/.test_build/pass"

rm -rf "$build_dir"
mkdir -p "$build_dir"

failed=0

while IFS= read -r test_file; do
	test_name="$(basename "$test_file" .cpp)"
	group_name="$(basename "$(dirname "$test_file")")"
	parent_name="$(basename "$(dirname "$(dirname "$test_file")")")"
	output_bin="$build_dir/${parent_name}_${group_name}_${test_name}"

	echo "==> compiling pass test: ${parent_name}/${group_name}/${test_name}"
	g++ -std=c++20 -Wall -Wextra -Werror -I"$root_dir/include" "$root_dir"/src/*.cpp "$test_file" -o "$output_bin"

	echo "==> running pass test: ${parent_name}/${group_name}/${test_name}"
	if ! "$output_bin"; then
		echo "PASS TEST FAILED AT RUNTIME: ${parent_name}/${group_name}/${test_name}"
		failed=1
	fi
done < <(find "$root_dir/tests/language_surface/pass" "$root_dir/tests/runtime_mechanics/pass" -name '*.cpp' | sort)

if [[ "$failed" -ne 0 ]]; then
	exit 1
fi

echo "All pass tests succeeded."

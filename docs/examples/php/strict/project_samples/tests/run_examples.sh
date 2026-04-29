#!/usr/bin/env bash
set -euo pipefail

samples_dir="$(cd "$(dirname "$0")/.." && pwd)"
repo_root="$(cd "$samples_dir/../../../../.." && pwd)"
scpp_cmd=(php "$repo_root/bin/scpp.php")
manifest_path="$samples_dir/tests/samples_manifest.txt"
expected_dir="$samples_dir/expected"

while IFS= read -r sample_id; do
	[[ -z "$sample_id" ]] && continue
	project_dir="$samples_dir/$sample_id"
	expected_path="$expected_dir/${sample_id}.stdout"
	binary_path="$project_dir/.prism/build/main"

	if [[ ! -d "$project_dir" ]]; then
		echo "Missing project sample directory for $sample_id" >&2
		exit 1
	fi

	if [[ ! -f "$expected_path" ]]; then
		echo "Missing expected output for $sample_id" >&2
		exit 1
	fi

	actual_path="$(mktemp)"
	stderr_path="$(mktemp)"
	build_stdout_path="$(mktemp)"
	build_stderr_path="$(mktemp)"

	(
		cd "$project_dir"
		"${scpp_cmd[@]}" build >"$build_stdout_path" 2>"$build_stderr_path"
	)

	if [[ ! -x "$binary_path" ]]; then
		echo "Expected built binary at $binary_path" >&2
		rm -f "$actual_path" "$stderr_path" "$build_stdout_path" "$build_stderr_path"
		exit 1
	fi

	if [[ -s "$build_stderr_path" ]]; then
		echo "Unexpected build stderr from $sample_id" >&2
		cat "$build_stderr_path" >&2
		rm -f "$actual_path" "$stderr_path" "$build_stdout_path" "$build_stderr_path"
		exit 1
	fi

	(
		cd "$project_dir"
		"$binary_path" >"$actual_path" 2>"$stderr_path"
	)

	if [[ -s "$stderr_path" ]]; then
		echo "Unexpected stderr from $sample_id" >&2
		cat "$stderr_path" >&2
		rm -f "$actual_path" "$stderr_path" "$build_stdout_path" "$build_stderr_path"
		exit 1
	fi

	if ! cmp -s "$expected_path" "$actual_path"; then
		echo "Output mismatch for $sample_id" >&2
		echo "Expected from $expected_path:" >&2
		cat "$expected_path" >&2
		echo "Actual:" >&2
		cat "$actual_path" >&2
		rm -f "$actual_path" "$stderr_path" "$build_stdout_path" "$build_stderr_path"
		exit 1
	fi

	echo "ok $sample_id"
	rm -f "$actual_path" "$stderr_path" "$build_stdout_path" "$build_stderr_path"
done < "$manifest_path"

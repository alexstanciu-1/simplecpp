#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
ROWS="${1:-10000}"
ITERATIONS="${2:-100}"
CXX="${CXX:-g++}"
BUILD_DIR="$(mktemp -d)"
trap 'rm -rf "$BUILD_DIR"' EXIT

"$CXX" -std=c++23 -O3 -DNDEBUG -I"$ROOT/runtime/include" \
	"$ROOT/tools/runtime_benchmarks/string_parts_builder_probe.cpp" \
	-o "$BUILD_DIR/string_parts_builder_probe"

"$BUILD_DIR/string_parts_builder_probe" "$ROWS" "$ITERATIONS"

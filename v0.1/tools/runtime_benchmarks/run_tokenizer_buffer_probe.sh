#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
SOURCE_BYTES="${1:-100000}"
ITERATIONS="${2:-100}"
CXX="${CXX:-g++}"
BUILD_DIR="$(mktemp -d)"
trap 'rm -rf "$BUILD_DIR"' EXIT

"$CXX" -std=c++23 -O3 -DNDEBUG -I"$ROOT/runtime/include" \
	"$ROOT/tools/runtime_benchmarks/tokenizer_buffer_probe.cpp" \
	-o "$BUILD_DIR/tokenizer_buffer_probe"

"$BUILD_DIR/tokenizer_buffer_probe" "$SOURCE_BYTES" "$ITERATIONS"

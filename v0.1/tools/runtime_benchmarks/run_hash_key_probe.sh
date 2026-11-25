#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
COUNT="${1:-100000}"
CXX="${CXX:-g++}"
BUILD_DIR="$(mktemp -d)"
trap 'rm -rf "$BUILD_DIR"' EXIT

"$CXX" -std=c++23 -O3 -DNDEBUG -I"$ROOT/runtime/include" \
	"$ROOT/tools/runtime_benchmarks/hash_key_probe.cpp" \
	-o "$BUILD_DIR/hash_key_probe"

"$BUILD_DIR/hash_key_probe" "$COUNT"

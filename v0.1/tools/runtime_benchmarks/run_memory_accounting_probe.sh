#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
COUNT="${1:-10000}"
CXX="${CXX:-g++}"
BUILD_DIR="$(mktemp -d)"
trap 'rm -rf "$BUILD_DIR"' EXIT

"$CXX" -std=c++23 -O3 -DNDEBUG -I"$ROOT/runtime/include" \
	"$ROOT/tools/runtime_benchmarks/memory_accounting_probe.cpp" \
	-o "$BUILD_DIR/memory_accounting_probe"

"$BUILD_DIR/memory_accounting_probe" "$COUNT"

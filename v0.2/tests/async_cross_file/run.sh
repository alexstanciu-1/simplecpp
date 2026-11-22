#!/usr/bin/env bash
set -euo pipefail

fixture=$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)
root=$(cd -- "$fixture/../../.." && pwd)
build=$(mktemp -d)
trap 'rm -r -- "$build"' EXIT
cxx=${CXX:-clang++}
flags=(-std=c++23 -O2 -g0 -Wall -Wextra -Werror -pthread)
private_include=(-I "$root/runtime/include")

mkdir "$build/public"
cp "$fixture/api.hpp" "$fixture/caller.cpp" "$build/public/"
"$cxx" "${flags[@]}" -MD -c "$build/public/caller.cpp" -o "$build/caller.o"
if grep -Eq '/(coroutine|async_core\.hpp|int_task_private\.hpp)([[:space:]]|$)' "$build/caller.d"; then
    echo "Coroutine dependency leaked into the public caller" >&2
    exit 1
fi
for unit in child parent; do
    "$cxx" "${flags[@]}" "${private_include[@]}" -c "$fixture/$unit.cpp" -o "$build/$unit.o"
done
"$cxx" "${flags[@]}" "${private_include[@]}" -c "$root/runtime/include/scpp/async_core.cpp" -o "$build/runtime.o"
"$cxx" "${flags[@]}" "$build/"{caller,parent,child,runtime}.o -o "$build/proof"
"$build/proof"
echo "$cxx: separate public caller, cross-file await and exception cleanup passed"

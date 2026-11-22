#!/usr/bin/env bash
set -euo pipefail
fixture=$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)
root=$(cd -- "$fixture/../../.." && pwd)
build=$(mktemp -d)
trap 'rm -r -- "$build"' EXIT
cxx=${CXX:-clang++}
flags=(-std=c++23 -O2 -g0 -Wall -Wextra -Werror -pthread)
runtime=(-I "$root/runtime/include")
mkdir "$build/public"
cp "$fixture/api.hpp" "$fixture/caller.cpp" "$build/public/"
"$cxx" "${flags[@]}" -MD -c "$build/public/caller.cpp" -o "$build/caller.o"
if grep -Eq '/(coroutine|async_core\.hpp|int_task_private\.hpp)([[:space:]]|$)' "$build/caller.d"; then
    echo "Coroutine dependency leaked into public caller" >&2
    exit 1
fi
"$cxx" "${flags[@]}" "${runtime[@]}" -c "$root/runtime/include/scpp/async_core.cpp" -o "$build/runtime.o"
for mode in baseline concrete; do
    includes=(-I "$fixture/$mode")
    objects=("$build/caller.o" "$build/runtime.o")
    if [[ $mode == baseline ]]; then
        includes+=("${runtime[@]}")
    else
        "$cxx" "${flags[@]}" "${runtime[@]}" -c "$fixture/concrete/int_task.cpp" -o "$build/task.o"
        objects+=("$build/task.o")
    fi
    for unit in child parent; do
        "$cxx" "${flags[@]}" "${includes[@]}" -MD -c "$fixture/$unit.cpp" -o "$build/$unit.o"
        if [[ $mode == concrete ]] && grep -Eq '/(async_core\.hpp|condition_variable|mutex|queue|deque)([[:space:]]|$)' "$build/$unit.d"; then
            echo "Scheduler dependency leaked into $unit" >&2
            exit 1
        fi
        objects+=("$build/$unit.o")
    done
    "$cxx" "${flags[@]}" "${objects[@]}" -o "$build/proof"
    "$build/proof"
    echo "$cxx $mode: cross-file await, result, failure and cleanup passed"
done

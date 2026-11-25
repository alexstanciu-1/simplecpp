# Native rebuild costs with retained C++ runtime objects
Doc Status: planning

This follow-up measures native compilation and linking only. Incremental semantic
preparation, C lowering, compiler-side caching and cache invalidation are excluded.
It treats a supplied application translation unit as the input and retains the
runtime, bridge and caller objects while rebuilding that unit.

## What changed from the first experiment

`handle_money.c` contains the accounting arithmetic and calls the narrow
`handle_abi.h` boundary. `handle_runtime.cpp` owns opaque handles containing actual
`scpp::shared_p<scpp::AmountResult>` values, constructs the original runtime-backed
AmountResult, and stores its actual `scpp::string_t` reason. It does not implement
accounting arithmetic. C returns an owning handle; the existing result-record
interface is just an adapter for the shared behavior-test driver.

The experiment therefore moves accounting code to C without replacing the result
object with a plain record. A temporary plain record transfers field values to
and from the bridge; it is not the retained object representation. Retaining a
handle copies the shared pointer, so aliases refer to the same object. Releasing
one handle leaves its retained alias usable. C++ exceptions are caught at bridge
entrypoints and represented by a failure status or null handle. Each owning handle
adds a small allocation around the runtime shared pointer; no runtime execution
speed claim is made.

All experiment files live here. No production compiler or runtime code changes.
The only refactor of the earlier harness extracts its unchanged boundary-case
oracle into `verification.py` so both experiments use one behavioral reference.

## Compared native paths

| Application object | Retained adapter | Runtime |
| --- | --- | --- |
| C17 `handle_money.c` | `handle_runtime.o` | existing `libruntime.so` |
| Same source compiled as C++23 | same `handle_runtime.o` | same `libruntime.so` |
| Original generated C++ MoneyRules | original `bridge.o` test adapter | same `libruntime.so` |

The lean C++ variant is an attribution control, not a requirement to keep C++
lowering. It shows which costs arise from application runtime exposure versus
language mode. The original test adapter makes the same C driver usable against
the original generated C++ implementation; its build cost is also excluded from
application rebuild measurements and recorded separately.

All links use Clang++ and its default system linker (GNU ld 2.42 on this host),
with the runtime shared library and an rpath to that library. No LTO or Mold flag
is used. Link times cover a tiny executable with one application object and one
adapter object; they do not predict full-accounting link latency.

## Measurement and validation

Run:

```sh
python3 specs/planning/results/accounting_c_vs_cpp_2026_10_18/run_handles.py
```

The runner builds a fresh runtime-only PCH and both adapters once per optimization
level, recording those costs separately. It compiles the common C driver once,
warms all three paths, then runs seven serial application-object rebuilds and
links per variant in shuffled order at each of `-O0` and `-O2`. Flags also include
`-g -fPIC -pipe`; call-depth guards are disabled consistently. Source hashes,
commands, individual samples, environment and load averages are recorded in
`handle_results.json`. PCHs, objects and binaries are temporary.

This is a **forced native rebuild experiment**, not an integrated incremental
compiler or build-cache benchmark: each round rebuilds the same application source
from scratch and links it. The input is stable to make the timing comparison
repeatable. No cache-hit observations are included in the compilation samples. Artifact SHA-256 hashes
and mtimes verify that runtime, PCH, adapter and caller artifacts remain unchanged
across the measured rounds. Runtime library construction is not timed.

Each final executable must match the independent integer oracle for 21,870 cases.
Both handle variants additionally check shared payload identity, different-object
identity, mutation through an alias, survival after original-handle release,
reason/valid field updates and null-handle handling. Successful tests cover this
bounded slice, not arbitrary object-model or failure equivalence. Fault injection,
reflection, dispatch, general exception propagation, container operations and leak
instrumentation remain outside this experiment.

## Incremental interpretation

Owning semantic preparation and lowering makes it possible to retain their results
and emit only affected native units. This experiment deliberately does not assign
a speculative cost to those stages. It measures the downstream cost that remains
when an application unit must actually be rebuilt, while already-compiled runtime
support is reused. It does not add another cache or prescribe a cache architecture.

A future production boundary must have a real runtime-owned ABI and explicit
lowering ownership. Here the adapter is specific to AmountResult: changing that
type's representation or the adapter contract can require rebuilding it. Arithmetic
body edits that preserve the boundary can reuse it. The existing runtime library
is also reusable in the C++ baseline, so both paths receive that benefit.

## Results — 2026-10-18

Clang 18.1.3, AMD Ryzen 5 PRO 4650G with Radeon Graphics, Linux-6.6.87.2-microsoft-standard-WSL2-x86_64-with-glibc2.39.

Median milliseconds across seven samples. Compile-plus-link is the median of each paired sum, not the sum of separate medians.

| Optimization | Application | Compile | Link | Compile + link |
| --- | --- | ---: | ---: | ---: |
| -O0 | C_handles | 55.70 | 149.32 | 205.81 |
| -O0 | CXX_handles_control | 57.13 | 146.07 | 202.47 |
| -O0 | generated_CXX_PCH | 1887.63 | 147.75 | 2036.30 |
| -O2 | C_handles | 112.29 | 155.67 | 270.44 |
| -O2 | CXX_handles_control | 108.31 | 157.59 | 265.91 |
| -O2 | generated_CXX_PCH | 2293.41 | 157.35 | 2447.60 |

One-time setup observations (seconds), excluded from the table:

| Optimization | Runtime PCH | Handle adapter | Original test adapter |
| --- | ---: | ---: | ---: |
| -O0 | 4.478 | 2.084 | 1.878 |
| -O2 | 12.919 | 2.577 | 6.478 |

All 131,220 numerical result comparisons passed (21,870 × three variants × two optimization levels). Both handle variants passed alias/lifetime checks at both optimization levels. All retained-artifact hash/mtime checks passed.

The native rebuild advantage was approximately 9.9× at O0 and 9.1× at O2 including linking. This excludes lowering and runtime construction. It is a four-operation slice, not a whole-accounting rebuild claim.

Host load averages: start [1.76708984375, 1.85595703125, 1.9228515625]; end [3.9921875, 2.50927734375, 2.142578125]. Host load was not isolated. Individual samples and ranges are retained in [handle_results.json](handle_results.json).

The same-source C++ control remained close to C. This is consistent with the major gain coming from moving runtime implementation exposure out of the application unit. It does not negate the C-lowering approach: C gives the incremental compiler a direct native target after it owns the source semantics.

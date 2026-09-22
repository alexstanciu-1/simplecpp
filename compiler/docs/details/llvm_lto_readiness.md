# LLVM LTO: parallel and incremental readiness
Doc Status: supporting

Status: production integration remains future work. The standalone runtime
preparation tool now has an isolated package proof described below. Keep normal native
compilation at `-O0` for now. This note does not schedule optimization,
benchmarking, new worker machinery or broader incremental recovery work.

## What LLVM provides

LTO works on LLVM bitcode and optimizes IR internally before producing native
objects for the final link. Its normal final product is an executable or native
library; saved intermediate IR is optional. Providing runtime bitcode can expose
runtime function bodies to cross-module optimization without replacing their
ABI contracts. Inlining is an opportunity, not a guarantee.
See [LLVM's LTO design](https://llvm.org/docs/LinkTimeOptimization.html).

Full LTO merges input IR into one module for optimization, making large builds
harder to scale in memory and time. ThinLTO combines compact module summaries
for global analysis, then performs importing, optimization and code generation
in parallel module backends. A configurable persistent cache supports reuse
between builds. Summary analysis and final native linking still take place;
incremental reuse does not mean updating only the changed file or patching an
existing executable in place.
See [ThinLTO](https://clang.llvm.org/docs/ThinLTO.html), including its
[worker controls](https://clang.llvm.org/docs/ThinLTO.html#controlling-backend-parallelism)
and [incremental cache](https://clang.llvm.org/docs/ThinLTO.html#incremental).

## Intended ownership in our compiler

ThinLTO is the preferred candidate to evaluate for a future optimized build mode.
Keep language checking and LLVM emission independent of that choice:

```text
Compiler selection and workers
    -> changed bitcode modules + retained unchanged modules
ThinLTO summary analysis
    -> valid cached native objects + parallel backend work for cache misses
Native link
    -> executable
```

Our compiler owns source-level dependencies and reuse of checked, lowered and
emitted results. LLVM should own validity of its optimized backend cache,
including dependencies introduced by cross-module optimization. Supply the
complete current link inputs, combining changed and retained bitcode, rather
than submitting only changed modules.

For example, if A calls B and LLVM inlines B into A, changing B's body can require
regenerating A's machine code. A's source, checked body and original IR can
remain reusable when their language-level dependencies are unchanged. Therefore
the current non-LTO rule that an unchanged module can retain its native object
must not be carried unchanged into the LTO path. Do not reproduce LLVM's import
dependency logic in our semantic stages.

Compiler worker limits and LLVM backend worker limits are separate. Coordinate
them when work overlaps; avoid giving every concurrent tool invocation an
unbounded set of workers. Parallel compilation does not automatically make the
generated program multithreaded.

## Future integration and proof

The expected changes belong to backend configuration, toolchain execution and
native artifact ownership/reuse. This is more than adding a linker flag, but
does not by itself require redesigning parsing or semantic analysis.

- Produce compatible bitcode and summaries for project modules and participating
  runtime code. Native-only library bodies remain unavailable for IR inlining.
- Make optimization mode, target, toolchain and runtime artifact identity part
  of the appropriate compatibility/invalidation contracts. Keep scheduling-only
  settings separate from code-generation choices.
- Configure a supported linker, cache location and worker budget. The current
  non-LTO object cache is not a substitute for ThinLTO's backend cache.
- When implementation is authorized, prove a full build and one increment with
  a cross-module body change, including a caller affected by inlining. Verify
  execution, valid reuse and configuration invalidation before claiming support.

An earlier isolated Clang 18/LLD probe compiled the existing Simple C++ string ABI
bridge and a small LLVM caller with ThinLTO, linked and printed `hello`. This
established a narrow toolchain feasibility result only: it did not prove PHP
prototype integration, actual inlining, parallel scaling or incremental caching.
Production configuration was not changed.

The [runtime preparation test](../../src-runtime-preparation/tests/run.php)
now consumes generated provider metadata, inline storage and prepared bridge
artifacts in ordinary, full-LTO and ThinLTO builds. It exercises real Simple C++
strings and a second C++ provider type through the same adaptation rules. With
Clang/LLD 18, saved optimized IR proves cross-module bridge-call elimination;
changing a provider body produces the new result with identical caller bitcode.
ThinLTO uses a persistent cache, without performance or cache-hit claims.
Prepared bitcode is passed directly to the linker; treating `.bc` as a normal
Clang source input can trigger another compilation. The [compiler-generated scalar consumer test](../../tests/integration/runtime_abi.php)
also proves full/ThinLTO execution, actual provider-call elimination and relinking
retained caller bitcode after a provider-body edit. See the [package contract](../../src-runtime-preparation/README.md).

The [current native build](native_executable.md) remains authoritative for
implemented behavior. The [foundations tracker](../planning/compiler_foundations.md#current-required-flow)
continues to limit required prototype proof to one full rebuild and one
incremental attempt; this future direction does not expand that scope.

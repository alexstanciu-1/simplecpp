# Initialization header separation
Doc Status: planning

Date: 2026-10-16. Implemented in the isolated accounting checkout based on
`bf7bd2e3`. The original compiler working tree and previous dev002 runtime copy
were not modified. This is header organization, not unit splitting.

## Implemented boundary

- `scpp/data_initializer_input.hpp`: input views, entries, buffers, generic population
  templates and the primary adapter declaration.
- `scpp/data_initializer_typed.hpp`: vector/hash/shared-hash adapters.
- `scpp/data_initializer_mixed.hpp`: the concrete mixed adapter.
- `scpp/data_initializer_dynamic.hpp`: the concrete dynamic adapter, including the
  complete hash table definition needed by its population calls.
- `scpp/data_initializer.hpp`: compatibility umbrella including all adapters.

Every original initializer definition is preserved byte-for-byte across the new
headers. No function body, representation, allocation policy, exception message or
inlining boundary changes. Core runtime metadata selects the typed adapter header;
compiler-owned input/mixed/dynamic bindings select their corresponding narrow
headers. There is no optimization-level branch for this split.

Generated accounting C++ differs only in include lines: 53 artifacts at O0/O1 and
65 at O2. After removing include lines, all 292 artifacts match the previous
checkpoint. The sample remains 93 generated C++ units. This intentionally changes
O2 include paths while preserving its generated function bodies and runtime bodies.

## Protocol

Remote workspace: `/home/alex-ai/accounting-opt-policy-20261016/initialization`.
Baseline is the previous allocation-outlining compiler under sibling `allocation`,
using the original sibling `repo/runtime`. Candidate uses its own copied `runtime`
with only this header/metadata overlay. The compiler itself is rebuilt at O2 through
the existing converter and legacy S2S, using the compiler's STAN-bypass policy.
Its source/header regeneration is from authored PHP, not a generated-C++ patch.

Settings: dev002 native Linux/Ryzen 9 5950X; Clang 18.1.3, Ninja 1.11.1, Mold 3.0.0
with 8 threads, Ninja 8 jobs, compiler 12 jobs, runtime-only PCH. OS caching is
allowed. Profiling is disabled in the timed matrix; traces run separately afterward.
Each invocation starts a fresh compiler process. These are not timings of retained
semantic-incremental processing.

Three rounds alternate baseline/candidate ordering at O0, O1 and O2. Each case runs
full, unchanged, the neutral workflow body edit (`20000` → `20000 + 0`), revert and
revert-unchanged. Edits are restored with Git. All full builds use fresh dedicated
build directories; runtime updates are not silently trusted through existing caches.
The compiler's documented manual-runtime-invalidation contract remains unchanged.

## Results

The native compiler O2 rebuild succeeded on its first attempt (one compiler object
and link, 7.35 s, GNU time maximum RSS 521 MiB). All 214 compiler source hashes and
six runtime header/metadata hashes match the tested checkout.

The accounting sample is 528,088 bytes of authored PHS. All **90 builds passed**,
each producing the expected **1,855 checks**. All 18 full-build PHP/native artifact
comparisons matched. Full builds compile 96 objects (93 generated + 3 runtime), one
PCH and link. Body edits/reverts rebuild one object, reuse 95 and reuse the PCH.
All 36 unchanged invocations execute zero native build actions.

Times are seconds, using the median-total trial of three. Memory is the maximum
sampled process-tree RSS across those trials, in MiB; shared pages can be counted
more than once and sampling is every 10 ms. `previous` is allocation outlining;
`split` is this change (the harness retains the variant label `outlined`).

| Mode | Full previous → split | Body edit previous → split | Full peak RSS previous → split | Body peak RSS previous → split | Split unchanged |
| --- | ---: | ---: | ---: | ---: | ---: |
| O0 | 9.014 → 8.795 | 1.964 → 1.890 | 1491 → 1473 | 284 → 277 | 1.048 |
| O1 | 17.297 → 17.103 | 4.436 → 4.327 | 1613 → 1604 | 276 → 270 | 1.064 |
| O2 | 23.771 → 23.098 | 8.794 → 8.713 | 1687 → 1648 | 391 → 384 | 1.061 |

Candidate stage breakdown. Compile is the union of active Clang intervals, including
PCH creation where needed, rather than a sum of concurrent jobs. Executable-ready
is link-driver completion. Other time is distributed throughout the request, not
an exclusively post-link phase.

| Case | C++ ready | Compile | Link | Other | Executable ready | Total |
| --- | ---: | ---: | ---: | ---: | ---: | ---: |
| O0 full | .787 | 7.595 | .054 | .360 | 8.724 | 8.795 |
| O0 body | .784 | .694 | .047 | .365 | 1.816 | 1.890 |
| O1 full | .798 | 15.940 | .041 | .324 | 17.068 | 17.103 |
| O1 body | .761 | 3.183 | .039 | .343 | 4.273 | 4.327 |
| O2 full | .780 | 21.936 | .048 | .334 | 23.051 | 23.098 |
| O2 body | .775 | 7.578 | .034 | .326 | 8.679 | 8.713 |

Separate isolated traces (single observations, not medians) show the intended
frontend saving. All values below are previous → split, seconds except RSS in MiB.

| Mode/unit | Clang frontend | LLVM backend | Driver wall | Process peak RSS |
| --- | ---: | ---: | ---: | ---: |
| O0 workflow_checks | .553 → .452 | .228 → .229 | .81 → .70 | 182.8 → 174.0 |
| O0 closing_checks | .450 → .357 | .146 → .146 | .62 → .53 | 173.2 → 165.0 |
| O0 warehouse | .767 → .747 | .207 → .207 | 1.00 → .98 | 193.1 → 191.5 |
| O1 workflow_checks | .562 → .465 | 2.727 → 2.698 | 3.32 → 3.20 | 185.5 → 184.6 |
| O1 closing_checks | .458 → .358 | 1.478 → 1.448 | 1.96 → 1.84 | 182.8 → 181.5 |
| O1 warehouse | .787 → .759 | 1.088 → 1.086 | 1.90 → 1.87 | 198.3 → 196.4 |

In each workflow/closing O0/O1 trace, the four concrete mixed/dynamic population
instantiation events disappear. This confirms the intended removal of unrelated
initializer work; the modest whole-build improvement should not be extrapolated
as a general percentage speedup.

The allocation runtime witness passed 42 timed executions (seven per configuration,
after warmup). Median previous → split: O0 .7007 → .7103 s, O1 .05573 → .05642 s.
Observed ranges overlap (.6907–.7121 vs .6975–.7194 s at O0; .05334–.05699 vs
.05591–.05970 s at O1). Candidate O2/O3 medians are .05382/.05480 s. Process peak
RSS is unchanged at 4 MiB O0 and 3.75 MiB O1; O2/O3 use 3.75 MiB. This short
witness does not establish exact performance equivalence, although all algorithm
bodies are unchanged.

Evidence: [comparison tables](initialization_headers_20261016/comparison.json),
[validated identities and trace assertions](initialization_headers_20261016/validation.json),
[raw dev002 archive](initialization_headers_20261016/evidence.tar.gz), and
[local checks, including the corrected initial failure](initialization_headers_20261016/local-checks.tar.gz).
The adjacent scripts preserve the experiment harness; they expect the documented
workspace layout and prior allocation checkpoint, rather than being a standalone
benchmark installer.

## Correctness and corrections

Standalone-header tests run without a PCH: each of five headers compiles independently,
forward/reverse combined include orders compile, and typed-only input lifetime and
exception-unwinding behavior matches the compatibility umbrella. A compile-time check
proves that the typed header does not expose the concrete mixed adapter.

The first standalone dynamic-header test correctly failed: `dynamic_value.hpp` only
forward-declares its packed table. The new dynamic adapter needed an explicit hash
header include. This same missing dependency caused two generated-C++ fixtures to
fail in the initial local suite. The header was corrected before accepted dev002
benchmarks. All initializer algorithm bodies remain untouched.

The existing local initialization suite's five PHP preparation/export checks pass.
Its 48 exported C++ programs plus the runtime input-lifetime witness all pass on dev002 against the corrected separate runtime, without a PCH. These cover typed,
mixed and dynamic data, nesting, key normalization, duplicate rejection with
null/false values, ordering, checked conversion failure, independent state and
input/factory unwinding.

All 21 additional native-compiler source witnesses pass and exercise typed/mixed/dynamic adapters at
O0/O1/O2, verify only the required adapter headers are emitted, check dynamic aliases,
null/false and nested reads, typed containers of mixed/dynamic elements, and
normalized duplicate-key failures at runtime.

These are bounded initializer and accounting proofs, not a full runtime/language
regression or an incremental/MT audit. Runtime templates may still contain legitimate
mixed dependencies internally; the split removes unrelated concrete initializer
bodies from typed translation units. Unit splitting remains deferred.

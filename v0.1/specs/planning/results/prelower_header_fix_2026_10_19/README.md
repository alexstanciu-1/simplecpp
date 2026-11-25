# Compiled output and key-validation boundary repair
Doc Status: supporting

The production backend now routes native as well as concrete stored echo operands
through `CPP_Runtime_Operations::output`. `CPP_Index_Write::key_check` routes
concrete writes through the existing `write_key` adapter; redundant validation
interface requests in `CPP_Concrete_Index` and the compound-only wrapper are removed.
No new retained model, registry, type recipe or runtime algorithm was added.
Both adapters borrow existing storage. The runtime still owns formatting and key
validation; emission still owns key/RHS sequencing and temporary lifetimes.

## Accounting result

Fresh production publication has no direct `scpp/keyed_path.hpp` or
`lang/php/support/php_string.hpp` includes in its application translation units.
The original runtime implementations remain compiled in the middleware.

| Application compilation | Median elapsed |
| --- | ---: |
| Saved pre-fix prepared output | 9.907 s |
| Regenerated production output | 7.387 s |

Reduction: **25.4% (2.520 s)**. Three alternating-order rounds, Clang 18.1.3,
C++23, O0, debug disabled, eager PCH, all 16 app units recompiled sequentially.
Runtime, layout and middleware objects are reused for these rounds. Median link
elapsed is 0.491 s before and 0.504 s after. Every executable (cold build plus six
replays) passes **998 checks, zero failures**. A subsequent unchanged native build
reuses 22 objects and performs zero compilation, PCH creation or linking.
The fresh middleware base/container object compilation takes 5.078 s, separately
from application timing and layout/PCH/runtime setup.

The matched baseline is freshly measured; do not compare this percentage directly
to the earlier diagnostic shim's 32%. This production change uses the general
existing adapters, including compiled key validation instead of the diagnostic
inline string-key no-op. Both measurements include runner/process-launch overhead.

## Focused behavior and incremental proof

`python3 compiler/my-try/tests/prelower_header_boundaries.py --results FRESH`
reuses the existing output, indexing and compound fixtures, running only O0.
Direct and composed output/counters must match. Output covers exact scalar bytes,
borrowed/temporary operands, ordered side effects and failure suppression, plus
request retirement/restoration, no-op fragment replay and configuration switching.
Indexing and compound cases cover nested references, mutation/replacement during
RHS evaluation, unset ordering, throwing copies and runtime failures. Native string
keys occur in the compound string-write case. Warm builds compile/link nothing.
The string/bool/float output requests and indexing-header exclusion are explicit
regression assertions.

Output and reference-only counters are all zero; complete indexing/compound workloads
retain their ordinary allocations and tracked payload copies exactly. These are
bounded behavioral/counter proofs, not full runtime coverage or primitive-copy
instrumentation. No O2, sanitizer rerun or native compiler rebuild was performed.
PHP ran through the shared runner's CLI fallback because FPM sockets were unavailable.

## Remaining scope and reproduction

This removes the two measured accounting leaks. Null output still uses its existing
sentinel path; other native fallback operations, shared handles and runtime string
internals can expose templates. Full template elimination remains separate work.

`summary.json` preserves samples. `run.py` records the accounting procedure and
depends on the preceding `/tmp/accounting-timing` publication, command manifest and
cache. Raw commands and compiler phase times are in `/tmp/accounting-header-fix`;
focused final proofs are in `/tmp/prelower-header-fix-final`. Production sources and
existing proof fixtures are the authority for regeneration; no generated output was
patched. See the [diagnostic experiment](../prelower_header_impact_2026_10_19/README.md).

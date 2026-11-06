# Metadata-driven scalar comparisons

Doc Status: supporting

The first slice implements `== != < <= > >=` for matching canonical int64 or
float64 operands, and `== !=` for matching bool or byte operands. Results are bool.
`int`/`int64` and `float`/`float64` are aliases of their respective canonical identities.
No conversion is inferred from a typed destination or the availability of a cast.
String/null comparisons, mixed numeric comparisons, strict identity and spaceship
remain deferred. Mixed numeric ordering already has a language contract; this
implementation boundary does not change it.

Contracts: [ordered evaluation](../../../specs/expression_evaluation.md),
[scalar types](../../../specs/compiler_scalars_v0_2.md), and
[distinct byte](../../specs/numeric_module.md). Same-type floating comparisons use
IEEE behavior: signed zeros compare equally; NaN equality/ordering is false and
inequality true. This slice does not introduce NaN literal syntax.

## Ownership and work

`ast_binary_expression` owns left/right expressions and a `binary_operator`
value. The common expression's type fact records successful preparation. There is
no separate prepared expression, copied operands, conversion list or allocated
operation record. For this slice, the syntax operator plus the prepared operand
type completely identifies the approved native operation.

The parser supports parentheses and relational precedence above equality, with
left association within each level. Grouping changes tree shape without adding a
wrapper node; leaves retain their original token spans. `1 < 2 < 3` fails because
its second comparison would mix bool and int, rather than behaving as a chained
range test. Integer arithmetic uses the same path; see [arithmetic](arithmetic.md). Logical operators reuse generated truthiness; see [logical expressions](logical.md).

`compiler.comparisons` in runtime metadata declares same-type admission, operator
names, bool result and native lowering strategy. The generator validates this
bounded vocabulary and emits direct preparation/lowering methods on canonical
type definitions. Removing a row/operator removes its generated admission.
Unsupported definitions inherit rejection. Inventory version is 12.

Preparation visits left then right exactly once and invokes the left definition's
generated admission handler. It performs no constant folding, callee-body scan,
second traversal or fixed point. Existing call/global watches remain owned by the
enclosing body. Reset, collection and panel inspection recurse over both children.

Lowering consumes the prepared tree. [Direct scalar lowering](direct_lowering.md) emits safe
expressions directly; other expressions retain a lambda with two ordered by-value snapshots. RHS calls cannot retroactively change the left
value through references. The generated type handler emits the comparison on those
snapshots. No new runtime helper or public include is needed.

## Verification

`tests/comparisons.php` covers all three flavors, every admitted operator family,
precedence/grouping, signed zero, invalid type pairs, byte/bool ordering rejection,
AST ownership, preparation visits, lowering purity, reset/reprepare, and incremental
signature-change rejection/recovery while retaining caller ASTs. Body-only edits
and no-op generations reuse prepared callers. Incremental emission stays deferred.

`tests/comparisons_native.py` runs the three flavors and a mixed-flavor multifile
program, checking exact output and exit status. It proves ordered calls, RHS
reference mutation, comparisons as arguments/returns, and loop conditions. Clang
stage timings and raw output are retained under the requested output directory.
Generator tests cover metadata removal and invalid comparison policies.

Commands:

```sh
XDEBUG_MODE=off php v0.2/compiler/tests/comparisons.php
XDEBUG_MODE=off python3 -B v0.2/compiler/tests/comparisons_native.py --output v0.2/build/comparisons-proof-2026-11-04
```

Focused proof on 2026-11-04: all four executables passed exact output/exit checks;
37 selected existing CTest checks, the loop PHP proof, generator checks and style
checks passed. PHP fixture generation (including semantic/incremental assertions)
was 86.813 ms; this is not a pure compilation benchmark.

| Fixture | Native wall ms | Clang frontend ms | LLVM backend ms | Optimizer ms | Codegen ms | Link command ms |
| --- | ---: | ---: | ---: | ---: | ---: | ---: |
| cpp | 141.318 | 14.523 | 12.113 | 6.665 | 4.023 | 67 |
| php | 133.122 | 14.952 | 13.442 | 6.826 | 5.135 | 58 |
| js | 142.612 | 17.715 | 14.115 | 7.824 | 4.618 | 60 |
| multifile | 117.868 | 12.895 | 18.319 | 6.842 | 6.054 | 53 |

O2/g0, libc++, mold, Ninja -j8, no PCH/LTO, fresh instrumented application objects,
prebuilt runtime archive. Trace categories overlap and are cumulative across units;
they are not additive wall-time stages. Raw fixtures, traces, build logs and results:
`v0.2/build/comparisons-proof-2026-11-04/` (untracked build evidence).

## Validated generation-time comparison plan

`compilerComparisonPlan()` validates comparison rows once and retains their operand
contract, result identity, lowering strategy and admitted operator spellings. Binary
handler generation consumes these fields directly; promotion validation shares the
same normalized comparison rows instead of re-reading/re-sorting the metadata.
C++ operator spelling remains a handwritten backend implementation table.

Existing restrictions and diagnostic strings remain: same-type comparison operands,
boolean results, native lowering, numeric ordering and bool/byte equality only. Mixed
ordering still follows its separately authorized promotion/conversion path. No metadata
schema, AST, preparation, runtime or emitter changes were required. The plan exists
only during API generation, not during application compilation.

All nine generated artifacts are byte-identical to the preceding revision. No extra
preparation calls, allocations, traversals or metadata lookups were introduced. Existing
comparison proofs assert single-pass operand preparation; incremental proofs verify
selective body reuse. Five focused CTests pass: generation, comparisons, mixed ordering,
conditions and runtime freshness. Sixteen native fixtures across cpp/php/js pass in
the comparison and mixed-ordering suites. The latter compares incremental recovery with
fresh compilation, including failed retries, signature/operator edits and no-op reuse.

### Focused preparation measurement

`build/comparison-plan-proof/preparation.php` parses outside the timed interval, then
measures standalone preparation orchestration (including its symbol publication and
signature setup). The synthetic source has 1,000 comparisons: integer, floating,
boolean and mixed numeric ordering. Every sample records exactly 3,001 expression
visits. One warmup and five measured samples per flavor; Xdebug disabled. This is a
current-cost measurement, not a before/after speedup claim; the executable preparation
code is unchanged. Observed ranges are noisy and do not establish flavor differences.

| Flavor | Median ms | Minimum ms | Maximum ms |
| --- | ---: | ---: | ---: |
| cpp | 17.53 | 12.74 | 19.90 |
| php | 13.33 | 8.12 | 16.07 |
| js | 10.87 | 7.50 | 12.79 |

### Native correctness builds

Separate, smaller mixed-ordering recovery fixtures; these are not native timings for
the 1,000-comparison preparation corpus. O2/g0, libc++, Ninja8, mold, no PCH/LTO,
prebuilt runtime, fresh instrumented application objects. Proof suites ran concurrently;
the cpp build experienced substantially higher observed wall/link times. These single
runs are not comparative benchmarks. Frontend/backend traces are cumulative and are not
additive wall-time components.

| Flavor | Native ms | Frontend ms | LLVM ms | Link ms |
| --- | ---: | ---: | ---: | ---: |
| cpp | 365.86 | 32.30 | 44.30 | 198.00 |
| php | 119.25 | 10.64 | 16.10 | 60.00 |
| js | 130.78 | 12.99 | 17.86 | 67.00 |

Raw fixtures, logs, native traces and preparation samples: `v0.2/build/comparison-plan-proof/`.


## String ordering

The compiler comparison metadata now admits all six string/string comparisons.
Generated handlers call the existing public runtime string operators and extract the
bool result through its named binding. No new preparation facts, structures, operand
traversals or runtime helpers are needed. Mixed string/numeric comparison remains rejected.

The existing string equality proof now covers unsigned-byte lexicographic ordering,
prefix lengths, embedded NUL, all 256 single-byte values, and incremental provider
signature errors/retries/deletion/recovery against fresh compilation in all flavors.
Preparation still visits each operand once; lowering leaves prepared facts unchanged.

Native proof: `build/string-ordering-proof-2026-11-06/results.json`. O2/g0, libc++,
Ninja8, mold, no PCH/LTO, prebuilt runtime, fresh instrumented app objects. Fifteen
fixtures pass. Main fixture single observations (not comparative benchmarks):

| Flavor | Native wall ms | Clang frontend ms | LLVM ms | Link ms |
| --- | ---: | ---: | ---: | ---: |
| cpp | 169.19 | 21.20 | 33.42 | 70 |
| php | 184.72 | 25.11 | 41.94 | 60 |
| js | 151.63 | 17.45 | 30.03 | 57 |

PHP fixture generation and incremental verification together took 119.53 ms.
Stage trace times are nested work measurements, not additive wall-time components.

## String-only concatenation

`compiler.concatenation` declares result/operand identity, compound availability,
runtime strategy and flavor admissions. The generated type uses the existing public
string addition API. The new `binary_syntax_allowed()` hook checks flavor restrictions
only on types with restricted operations; ordinary numeric preparation does not
walk to the source root. Same-type pair/result admission and existing compound
write-back remain shared. PHP dot adds one binary enum identity; no new AST node
or prepared fact is introduced.

The combined string proof covers concatenation, empty/NUL bytes, local compounds,
reference side effects, rejected text conversions and PHP string-plus rejection.
Provider signature failures and recovery are compared with fresh output in all
flavors. Native checks also cover compound writes through reference parameters,
self-concatenation and a nonexecuted for-header compound. Raw results:
`build/string-concat-proof-2026-11-06-final/results.json`.

| Flavor | Native wall ms | Frontend ms | LLVM ms | Link ms |
| --- | ---: | ---: | ---: | ---: |
| cpp | 194.68 | 26.77 | 51.17 | 66 |
| php | 208.88 | 29.55 | 61.03 | 65 |
| js | 169.57 | 20.57 | 46.04 | 58 |

Same configuration as above; single observations. PHP fixture generation and
incremental verification took 149.10 ms.

## Plain null equality

The metadata comparison table admits null/null equal/not-equal only. Existing
same-type admission, operand snapshots and bool results suffice; no AST/type API
changes or new preparation facts are needed. Both operand effects are preserved.
Tests reject null versus each other scalar family and compare retained-session
recovery from a provider type change with fresh output in all flavors.

Raw results: `build/null-equality-proof-2026-11-06-final/results.json`.
18 native fixtures passed; PHP generation and incremental verification took
169.16 ms. Same O2/g0/Ninja8/mold configuration as above.

| Flavor (null recovery) | Native wall ms | Frontend ms | LLVM ms | Link ms |
| --- | ---: | ---: | ---: | ---: |
| cpp | 115.12 | 11.27 | 15.24 | 55 |
| php | 145.53 | 13.75 | 17.74 | 76 |
| js | 120.71 | 10.62 | 16.81 | 61 |

## Strict identity

`compiler.strict_identity` owns participating canonical types and same/different
type policies. Generation reuses the existing validated comparison plan for
same-type value equality; it does not copy a handwritten comparison/conversion
matrix into preparation. Generated admission checks canonical RHS identities,
without promotion, and returns bool.

The common `lower_cpp_binary()` interface now also receives the prepared RHS type.
This lets generated lowering select same-type value comparison or a fixed result
for distinct representations. Existing ordered snapshots still evaluate both
operands; discarded snapshots are explicitly consumed. Safety metadata keeps
identity conservative. No AST structures, prepared records, additional traversal,
constant folding or runtime API changes were introduced.

The proof covers every active scalar pair in both directions, aliases, NUL strings,
IEEE boundaries and failure/effect ordering. Retained-session provider changes across
byte/int/float/string/null and back to uint8 are compared with fresh compilation,
including deletion, failed retries and recovery.

Raw proof: `build/strict-identity-proof-2026-11-06-final/results.json`.
36 native fixtures passed. PHP fixture generation and incremental verification:
316.22 ms. O2/g0, libc++, Ninja8, mold, no PCH/LTO, prebuilt
runtime and fresh instrumented app objects. Single main-fixture measurements:

| Flavor | Native wall ms | Frontend ms | LLVM ms | Link ms |
| --- | ---: | ---: | ---: | ---: |
| cpp | 230.85 | 40.37 | 71.49 | 72 |
| php | 308.97 | 48.72 | 101.18 | 100 |
| js | 201.99 | 36.55 | 64.34 | 57 |

## Three-way comparison

`compiler.three_way` owns participating representations, result identity, promotion
policies and lowering strategies. Its normalizer reuses integer-domain selection and
existing generated conversions. Same-type and promoted execution rows enter the
existing binary plan. Preparation still visits each operand once and retains the
same execution identity and conversion facts.

Integer results lower to the difference of ordered comparisons. Strings use the
existing public bytewise comparison operators and named bool extraction binding.
The named `float64_compare` helper rejects NaN before producing int64 -1/0/1.
The only runtime ABI addition is that helper in the minimal arithmetic public header.
No new AST classes or prepared-fact fields were added; the binary enum adds compare.

Proofs cover mixed byte/int/float pairs, rounding above 2^53, unsigned-byte string
ordering, NaN/infinity/signed zero, and signature-change failure/recovery against fresh
compilation. Metadata subset and malformed-policy checks validate generation.
Raw native proof: `build/spaceship-proof-2026-11-06/results.json`.
45 fixtures passed; PHP generation and incremental verification: 304.08 ms.

| Flavor (mixed float recovery) | Native wall ms | Frontend ms | LLVM ms | Link ms |
| --- | ---: | ---: | ---: | ---: |
| cpp | 154.79 | 15.24 | 19.97 | 74 |
| php | 116.89 | 9.84 | 15.11 | 58 |
| js | 123.67 | 11.87 | 18.03 | 62 |

O2/g0, libc++, Ninja8, mold, no PCH/LTO, rebuilt then prebuilt runtime, fresh
application objects. Single observations; trace work is not additive wall time.

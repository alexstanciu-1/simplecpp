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

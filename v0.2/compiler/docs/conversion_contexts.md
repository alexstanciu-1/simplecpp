# Conversion contexts and generated operator promotion

Doc Status: supporting

The [scalar contract](../../../specs/compiler_scalars_v0_2.md#conversion-contexts-and-mixed-ordering--2026-11-05)
and [mixed-ordering semantics](../../../specs/expression_evaluation.md#mixed-canonical-numeric-ordering--2026-10-19)
own this slice. It adds plain int64/float64 `< <= > >=` in both operand orders.
Mixed arithmetic is also admitted as described in [mixed arithmetic](mixed_arithmetic.md).
Float-destination compounds are explicitly admitted by [compound promotion](mixed_compounds.md).
[Mixed equality](mixed_equality.md) also uses this path; carriers and conversion-chain search remain deferred.

## Metadata and generated interfaces

`compiler.conversion_families` now owns family algorithms and context permissions;
[family expansion](family_conversions.md) produces transient concrete conversion rows.
Explicit casts and typed destinations admit the active numeric conversions; integer
write-back also has its dedicated compound context. The uint8-intent `conversion_context` enum also names `assignment`
and `inference`. All callers now pass their actual boundary to
`prepare_conversion(value, context)`. Identity remains a base-class operation;
reference matching and condition truthiness remain separate.

`compiler.promotions` contains directional rows with left/right types, an operator
list, execution type float64 and result bool_t for comparisons or float64 for arithmetic. Generator validation requires the
existing int64 → float64 `numeric_floating` definition and each selected float execution operation.
Rows may not authorize unsupported types/operators, missing operations or duplicates.
The conversion context list does not contain a general operator permission.

Generated `prepare_binary()` dispatches directly on operand types and the operator.
The selected promotion row authorizes the rounding conversion and supplies its
existing `value_conversion` identity. This bounded conversion needs no value/range
check. The generator does not duplicate its C++ implementation: the emitter calls
the existing execution-type `lower_cpp_conversion()` before `lower_cpp_binary()`.
Future checked promotions require their own reviewed admission; they are not enabled
by this slice. Generated inventory version is 16.

Same-type `binary_result()` is unchanged. Compound preparation uses `prepare_compound()` and `supports_compound()`.
Only explicit `compound_operators` permissions enable mixed writes.
Changing contexts to admit assignment would be a separate metadata/policy decision.

## Structure changes and performance

- New integer-backed `conversion_context` enum; conversion method signatures replace a boolean with that enum.
- `ast_binary_expression` adds a non-owning execution-type fact and left/right `value_conversion` facts.
- Settlement records these facts; reset clears the execution link and restores identity conversions.
- Prepared inspection exposes the execution type and both decisions for eager binary expressions.
- No additional AST nodes, prepared-operation objects, conversion lists, watchers or queues.

Operands retain their original types. Common preparation visits each child once,
then generated dispatch settles the result. No metadata lookup, common-type search,
extra source traversal or constant folding occurs during preparation. Same-type
lowering skips identity conversion calls/header collection. Mixed conversion runs
once per converted operand. Existing sequencing snapshots remain in place, including
conversion before a later operand's side effects; broader order work remains deferred.

## Verification

All three flavors test every ordering in both directions, rounding above 2^53,
int64 limits, signed zero, NaN/infinity through native observers and call evaluation
once in source order. Preparation visit counts, facts, reset/reprepare, emission
purity and unchanged assignment/compound restrictions are checked. Generator tests
cover removed/subset contexts, removed promotions, missing conversion definitions,
duplicates and invalid contexts/operators/execution types.

Incremental cases exercise provider body-only changes, mixed-to-same-type transitions,
signature failure, retries without edits, literal overflow, recovery, caller operator
edits and no-op reuse. Unchanged caller ASTs survive; successful generated C++ and
headers equal fresh compilation byte for byte. Recovery/operator-edit programs run
natively. Conversion contexts also pass the existing scalar, cast, function, assignment
and compound suites.

57 selected compiler CTests and three native regression suites passed. Twelve new
mixed-ordering native fixtures passed. Compiler style and generated freshness are
checked separately. These tests do not claim new source support for NaN literals.

```sh
XDEBUG_MODE=off php v0.2/compiler/tests/mixed_ordering.php
XDEBUG_MODE=off python3 -B v0.2/compiler/tests/mixed_ordering_native.py --output v0.2/build/mixed-ordering-proof-new
```

## Clang timings

O2/g0, libc++, Ninja8, mold, no PCH/LTO, prebuilt runtime; fresh instrumented
application objects in a new output directory. Single correctness samples, not
comparative benchmark averages. Frontend/LLVM totals accumulate across units;
optimizer/codegen overlap LLVM and are not additive wall-time components.

| Main fixture | Native wall ms | Frontend ms | LLVM backend ms | Optimizer ms | Codegen ms | Link ms |
| --- | ---: | ---: | ---: | ---: | ---: | ---: |
| cpp | 149.10 | 10.82 | 11.53 | 3.19 | 6.01 | 69.00 |
| php | 128.34 | 10.94 | 9.06 | 3.91 | 3.32 | 62.00 |
| js | 126.54 | 9.10 | 8.32 | 3.31 | 3.47 | 63.00 |

PHP fixture generation plus semantic/incremental checks: 182.89 ms.
Recovery: native wall 136.97–146.76 ms, frontend 12.72–17.11 ms, LLVM 19.59–23.25 ms, link 61.00–74.00 ms.
Operator edit: native wall 128.95–133.82 ms, frontend 13.00–15.67 ms, LLVM 18.76–21.11 ms, link 59.00–67.00 ms.
Native nonfinite observers: native wall 162.36–190.11 ms, frontend 27.01–38.04 ms, LLVM 15.50–25.43 ms, link 71.00–75.00 ms.

Raw artifacts: `v0.2/build/mixed-ordering-proof-2026-11-05-final/`.

## Generator matrix consolidation — 2026-11-05

The generator now builds one validated conversion plan, indexed by source/destination,
from the declared conversion rows. Boundary-handler rendering and promotion rendering
share that plan. The handwritten exact type-pair whitelist has been removed.
Strategy implementations retain category/representation guards: these validate whether
an algorithm can handle a row, and do not create conversions absent from metadata.
Categories come from runtime type declarations; byte remains a distinct category.
The widening strategy additionally verifies that the destination represents the source domain.

Each conversion row now has `promotion_operators`. Only the int64 → float64 row
lists `== != < <= > >= + - * /`; other rows have empty lists. Context permission and promotion
permission are independent. Checked promotions remain rejected because the current
promotion path cannot perform their required value-dependent preparation.

Promotion generation resolves left → execution and right → execution through the
shared plan (identity needs no row). It derives result/execution class references
from the promotion row and checks the execution operation's result and operator
availability. It no longer contains a fixed operand-pair whitelist, int64-specific
conversion search, fixed bool/float class emission or int64-specific side selection.

All eight generated compiler artifacts, including inventory version 16, are byte-for-byte
identical to their pre-refactor versions. Consequently application compiler structures,
source admission/diagnostics, preparation/lowering code and generated native behavior
are unchanged. No inventory bump is needed for identical output. SHA-256 evidence is
retained at `v0.2/build/conversion-matrix-refactor/generated-parity.json`.

Generator tests check all 252 combinations of seven strategies and six source/destination
types against an independent test oracle: eight conversions passed at that checkpoint.
The subsequent family slice extends this oracle to twelve active numeric conversions.
They also check permission removal/subsets and rejection of checked promotions.
Malformed metadata has explicit generator diagnostics; the new permission field adds
new validation without changing source-program diagnostics. Generated code is unchanged,
so this slice makes no application-compilation speedup claim. Validation/indexing now
runs once per generator invocation rather than once per generated destination type.

Validation after consolidation: nine focused semantic/generator/freshness CTests and
two native cast/mixed-ordering CTests passed, including all three flavors and
incremental recovery. The generated artifacts remain byte-identical after validation.

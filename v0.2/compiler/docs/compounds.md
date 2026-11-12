# Compound assignment statements

Doc Status: supporting

The [integer compound extension](integer_compounds.md) admits `+= -= *= /= %=`
for int64/uint8 locals and value/reference parameters, including mixed operands and
checked result write-back. The [float64 extension](float_compounds.md) and
[mixed float/int extension](mixed_compounds.md) retain their existing permissions.
Globals, fields/indexes and compound expression results remain deferred.
[For header clauses](for.md) reuse the same assignment mechanism.

## One syntax and preparation owner

`ast_assignment_statement` adds one nullable `binary_operator` property: null means
plain assignment, otherwise the value identifies the underlying binary operation.
The existing destination name, RHS child, resolved storage binding and write-back
conversion remain the owners. No synthetic read/binary/assignment tree is constructed.
The token-to-operator map is shared; adding a recognized spelling does not itself
admit operand types. PHP compound writes require an existing binding, unlike plain
first writes.

Generated compound handlers attach calculation identity, operand conversions and
result write-back to the existing assignment AST. Preparation resolves the destination
once and prepares the RHS once. Same-type admission reuses `binary_result()` and
`supports_compound($operator)`; mixed handlers consume the generated promotion/conversion plan.
There is no synthetic expression tree. The [integer extension](integer_compounds.md)
describes the prepared fields and separate conversion context.

A compound result remains unknown: arithmetic constant folding is not introduced.
The completed write replaces the latest-write fact, so subsequent reads cannot
reuse either the old initializer or the RHS as the new destination value. Reference
parameters keep their existing conservative alias handling. No new traversal,
prepared record, listener or dependency graph is introduced.

## Lowering and failures

For a plain signed 64-bit integer local, the generated handler supplies `+=`, `-=`, `*=`, `&=`, `|=` and `^=`. The emitter uses these directly with a local or integer literal RHS when the prepared left, right and write-back conversions are identity. A reference-mutating call requires the value held before the call. A scoped C++ block preserves the old destination before RHS evaluation, then uses
the generated binary lowering and performs one assignment:

```cpp
{ const auto compound_previous = local_0;
  local_0 = (compound_previous + change(local_0)); }
```

The scoped name can be reused across statements. Casts/calls and nested RHS
expressions retain their existing lowering. The compound mechanism introduces no
lambda and does not expand general expression-order machinery. Only division and
remainder require the existing public arithmetic header/helpers.

A failed RHS or operation prevents the compound store. Prior RHS writes remain;
there is no rollback. Native signed-overflow limitations and zero-divisor errors
are unchanged. General lvalue address/owner retention is deferred with complex
destinations. When a snapshot is needed, standalone statements and compound loop initializers each use a
statement-local snapshot. A compound loop update saves its destination in a
separate loop-scoped temporary before evaluating its RHS.
Operator/type admission and write-back generation are identical.

## Mining and proof

Reviewed the normative compound-update section in `specs/expression_evaluation.md`,
`my-try/04_analyze/operators/mutations.php::prepare_compound` and its catalog/contracts.
Retained old-value preservation, shared binary admission and compute-before-store.
Did not adopt donor carrier/indexed mutation records, expression-result snapshots
or generalized conversion protocols.

`tests/compounds.php` checks all flavors and operators, alias spellings, locals,
value/reference parameters, self reads, explicit casts, scope and loop bodies,
unsupported destinations/types, one RHS visit, inspection,
reset and emission purity. Incremental tests cover signature failure/recovery,
operator edits, unchanged caller AST/tokens, body-only edits and no-op reuse.

Native proofs also inspect reference storage after RHS/operation failures using a
test-only C++ catch. Zero division/remainder must leave the previous value intact;
RHS mutation followed by failure must preserve the mutation. A narrowing conversion
after a compound write verifies stale constant facts cannot suppress runtime checks.


## Recorded proof — 2026-11-05

All 46 selected compiler CTest checks passed (`^compiler_`, excluding native suites
and the persistent-service harness). Generator admission/freshness/cleanup checks
and writing checks (115 compiler PHP files) passed. All 21 compound native programs
and 16 existing arithmetic native regression programs passed. Raw fixtures,
generated C++, traces and logs are retained locally under
`v0.2/build/compounds-proof-2026-11-05/` (ignored build output).

Main compound fixture timings: single instrumented fresh builds, not medians or a
comparison of flavor performance. O2/g0, libc++, Ninja8, mold, no PCH/LTO, prebuilt
runtime. Trace durations are not additive wall time; optimizer/codegen are parts of
LLVM work, and link measures command duration. The JS run took noticeably longer;
these samples do not establish its cause or a flavor-specific regression.

| Flavor | Native wall ms | Frontend ms | LLVM backend ms | Optimizer ms | Codegen ms | Link ms |
| --- | ---: | ---: | ---: | ---: | ---: | ---: |
| cpp | 160.52 | 14.33 | 15.53 | 7.54 | 6.32 | 78.00 |
| php | 157.97 | 13.95 | 13.63 | 6.16 | 5.97 | 79.00 |
| js | 426.70 | 30.07 | 38.34 | 17.09 | 16.57 | 223.00 |

PHP fixture generation and all associated assertions took 98.83 ms. This includes
multiple compilations and incremental scenarios, not just one preparation pass.

Assignments now inherit their name and prepared storage binding from `ast_storage_write`, shared with [discarded-value updates](updates.md). Compound RHS/conversion ownership is unchanged.

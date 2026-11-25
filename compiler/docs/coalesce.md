# Published nullable coalescing

Doc Status: supporting

The first current-compiler slice accepts `nullable<T> ?? T` when the nullable
carrier is published and the fallback has exactly its canonical payload type.
It returns an owned payload, preserving the source carrier. This availability
boundary does not replace the broader [conditional matrix](../../specs/conditional_expression_matrix.md).
Application carriers, homogeneous wrapper fallbacks, result/sentinel families,
bare-null left operands and widening joins are deferred for `??`.

## Ownership and preparation

The parser reuses binary syntax and its existing operand ownership. `??` is right
associative, below `||` and above `xor`. No AST field, index or preparation pass is
added. Preparation visits both operands once, validates the generated carrier
capability and exact payload identity, and settles the existing binary result.
Conditional reference calls conservatively invalidate affected value facts.

The carrier generator owns admission: nullable family with exactly the value and
empty states. Presence and extraction use the carrier's generated methods. The
backend retains the left place once, observes presence, and extracts only on the
present branch. An explicitly typed expression lambda returns an owned value;
the fallback remains inside its selected branch. This lambda preserves required
laziness, single evaluation and copy semantics. It is not eager-order scaffolding.

## Verification

`tests/coalesce.php` verifies owned syntax, right association, single-pass visits,
reset/reprepare and pure emission/inspection. `tests/coalesce_native.py` verifies
all flavors on Clang/GCC: present zero/false/empty string, precedence, surrounding
short circuit, one left evaluation, lazy fallback and failures, copied strings
with NUL and vector independence. It also checks conservative fact invalidation
for a conditional reference call before a checked conversion.

Retained sessions cover body/signature edits, repeated diagnostics, source deletion
and restoration, fresh-output equivalence, unchanged caller object reuse and no-op
builds. Unsupported combinations are rejected even when the fallback is skipped.
The generator carrier-provider suite verifies export placement and retirement.

## Conditional assignment

Statement-form `??=` reuses `ast_assignment_statement` with the coalesce operator
and its ordinary assignment conversion. `calculates_value()` distinguishes eager
compound calculations from conditional writes, so no fabricated calculation type
or new AST field is retained. Conditional writes do not publish the RHS as an
unconditional constant. The usual conservative reference-call invalidation remains.

Admission requires a declared local and the same generated nullable capability.
The backend emits a presence-guarded `if` containing ordinary assignment, without
a lambda, snapshot or payload extraction. Conversions and RHS effects occur only
in that branch. Null and same-carrier assignments follow existing assignment rules;
implicit narrowing is not broadened. Parameters, properties, indexed destinations,
for-header writes and assignment expressions remain deferred.

`tests/coalesce_assignment.php` checks token boundaries, shared syntax ownership,
single-pass preparation, absence of invented constants, pure inspections/emission
and reset/reprepare. Its native companion covers all flavors on Clang/GCC, skipped
and selected effects, explicit checked casts, destination mutation by fallback,
NUL string/vector copies, null/same-carrier assignments and invalid-source rejection.
Retained tests cover body and signature edits, repeated failure, deletion/restoration,
fresh-output equivalence, native caller reuse and no-op builds.

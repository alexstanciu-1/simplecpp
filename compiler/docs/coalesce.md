# Published nullable coalescing

Doc Status: supporting

The compiler accepts `nullable<T> ?? T` when the nullable
carrier is published and the fallback has exactly its canonical payload type.
It returns an owned payload, preserving the source carrier. This availability
boundary does not replace the broader [conditional matrix](../../specs/conditional_expression_matrix.md).
Two `nullable<int>` operands also produce an owned `int`. A present zero skips
the fallback. If both are empty, evaluation throws
`std::domain_error("coalesce_selected_branch_has_no_usable_value_domain")`.

Two class handles of the exact same type (`N ?? N` or `?N ?? ?N`) select by
handle presence and preserve the chosen allocation. Two empty optional handles
produce an empty optional handle. Both forms evaluate the left once and the
fallback only on absence. Mixed required/optional handles, upcasts, other
homogeneous wrappers and result/sentinel families remain deferred.

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

Declared locals and writable declared PHP instance fields use the same generated nullable capability.
The backend emits a presence-guarded `if` containing ordinary assignment, without
a lambda, snapshot or payload extraction. Conversions and RHS effects occur only
in that branch. Null and same-carrier assignments follow existing assignment rules;
implicit narrowing is not broadened. Parameters, static properties, indexed destinations beyond the supported PHP vector/hash paths,
for-header writes and assignment expressions remain deferred. For a field, lowering
retains the selected receiver and binds the field once before checking presence.
A local PHP hash keeps the normalized key across its entry presence probe and the guarded
keyed store. No slot reference survives fallback evaluation. Nullable shared elements
use the same owner-presence probe as indexed `isset`.
Concrete recipes select the entry-presence binding for published and application collection types.
The `key_lookup` protocol uses the validated keyed read and supplies the fused owner probe's
native name to both the compiler and the concrete runtime generator.
The `checked_slot` protocol uses checked slot access for the presence probe, avoiding element copies.
Only the saved index survives that probe. The ordinary vector setter checks bounds
again after the fallback, including when the vector has been rebound or cleared.
For an indexed instance field, the collection root lowering retains the holder and
binds its field before key evaluation. The conditional store uses that same field
after fallback effects, including replacement of the collection.
For two-key hash-of-vector and vector-of-hash paths, lowering saves both keys,
probes the leaf through a checked parent slot, then materializes the converted
fallback before looking up the parent again for the store.
The native field test checks rebinding the last owner, skipping the fallback for
zero, writes made by a throwing fallback and readonly declaration edit/recovery.

`tests/coalesce_assignment.php` checks token boundaries, shared syntax ownership,
single-pass preparation, absence of invented constants, pure inspections/emission
and reset/reprepare. Its native companion covers all flavors on Clang/GCC, skipped
and selected effects, explicit checked casts, destination mutation by fallback,
NUL string/vector copies, null/same-carrier assignments and invalid-source rejection.
Retained tests cover body and signature edits, repeated failure, deletion/restoration,
fresh-output equivalence, native caller reuse and no-op builds.

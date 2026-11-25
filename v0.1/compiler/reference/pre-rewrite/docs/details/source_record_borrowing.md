# Source-function record borrowing
Doc Status: supporting

Source functions can borrow source-defined or provided records for one call.
Const references also support managed/owning records; explicit mutable references
remain restricted to plain records.
The same rules apply to demanded template specializations. The record retains its
nominal type; a reference parameter changes access and passing, not its value type.

```php
struct sample { public int32 $value; }
function read(const sample &$record): int32 { return $record->value; }
function write(sample &$record, $value int32): void { $record->value = $value; }
```

`Type &$name` and `const Type &$name` follow Simple C++'s explicit parameter-reference
contract. Existing value parameters retain the prototype spelling `$name Type`.
Const value parameters and scalar references are outside this slice.

## Semantics and boundaries

- Arguments must be initialized local records or incoming record references of the
  same nominal type. A mutable reference may forward as const or mutable; a const
  reference may forward only as const. No implicit representation-based conversion.
- A mutable parameter aliases caller storage. Field writes and whole-record assignment
  change that object; assignment does not rebind the reference.
- Reading a record into an ordinary local creates an independent value copy. Const
  restricts writes through the reference, not the mutability of this independent copy.
- Multiple plain-record parameters may alias one object, including const and mutable parameters.
  Const access does not assert that the object cannot change through another alias.
  No `noalias` or `readonly` LLVM parameter attributes are inferred.
- Arguments evaluate left to right. Borrowing keeps the address: later argument
  evaluation can change the object before the callee reads it.
- Call-scoped access does not transfer ownership. Ending an incoming reference's local
  binding never schedules destruction of caller storage.
- Temporary record borrowing, scalar references, mutable opaque references, reference locals/results,
  retained/escaping references and source aggregate value parameters remain
  unsupported. Nested and managed fields now use shared lifecycle composition;
  ownership effects are checked through [parameter summaries](owning_storage_fields.md).

Upstream contracts: [references](../../../../../simple_cpp_compiler/vendor/simple_cpp/specs/references.md)
and [native reference safety](../../../../../simple_cpp_compiler/vendor/simple_cpp/specs/native_reference_safety.md).
Plain record borrowing adds no exclusive-alias rule. Stable owning-record borrows
also survive backing-storage replacement. Allocation-backed element borrows prevent
invalidation; source ownership summaries require distinct arguments where resource
access order cannot safely admit aliases. See [the bounded alias contract](owning_storage_fields.md#compact-source-contracts).
This does not promise retained dynamic interior references.

## Owners and flow

1. [Parser](../../src/03_parse/handlers/declarations.php) preserves one optional
   reference marker after the logical variable/type children. `Syntax_Access` validates
   this structure; the original AST remains shared unchanged by semantic workers.
2. [Parameter_Contracts](../../src/04_analyze/resolve_types/utilities/parameter_contracts.php)
   interprets source annotations. Signature workers normalize source and provider
   passing modes into private `signature_request` outputs; `Signature_Join` validates
   annotations/provider contracts before writing the private type candidate.
3. [Type_Store](../../src/04_analyze/type_model/data/store.php) interns return
   type, ordered parameter types **and passing modes**. `signature_representation`
   carries the ordered mode vector. Shared `type_member` rows remain type/name data;
   no parameter-only property is added to record fields. Provider ABI extensions and
   byte-span layout remain in their existing backend contracts.
4. [Body checking](../../src/04_analyze/check_bodies/handlers/expressions.php)
   reads the selected signature for all calls. It selects value reads versus existing
   storage, rejects const writes/mutable forwarding, and retains signature dependencies.
   `Checked_Body::local_passing()` exposes entry binding ownership to lifetime/lowering.
5. [Lifetime analysis](../../src/04_analyze/analyze_lifetimes/handlers/values.php)
   checks passing against the checked signature, tracks live argument access and ends
   it at the call. Reference bindings are non-owning; ordinary copied locals retain
   their own lifetime.
6. [Lowering](../../src/05_generate_code/lower/handlers/locals.php) gives each
   local one storage slot. A slot's nonzero `incoming_parameter` identifies borrowed
   storage; zero denotes an owned allocation. Entry parameter instructions bind the
   incoming address without allocating/copying a record.
7. [LLVM emission](../../src/05_generate_code/emit_llvm/body.php) resolves a
   root slot to `%pN` or `%sN` through `slot_operand()`. Loads, stores, field addresses
   and forwarding share that mapping. Borrowed call parameters use `ptr`; current
   address passing requires stack and ABI address space zero.

Passing modes and slot origins are exported in the corresponding debug views.
Entity identities continue to use allocated IDs/exact keys. No runtime side table,
fixture-specific type test or extra template specialization mechanism is introduced.

## MT, incremental and memory

The existing selected signature/body/lifetime/backend/lowering work units remain the
only execution path. Each worker consumes fixed inputs and returns private output;
joins own acceptance. No coordinator-side body or alias analysis is added.

A passing-mode change changes the semantic signature contract. Declaration edits use
existing full-selection fallback; an ordinary body edit reuses stable signatures,
layouts and unchanged callable outputs. Retained snapshots remain immutable.

Storage adds one integer origin per slot; signatures add an ordered passing-mode
vector. Permission/address lookup is constant-time. There is no analysis of possible
alias combinations or runtime reference-count work for these non-owning borrows.

## Evidence and next step

[Source proof](../../tests/integration/source_record_borrows.php) covers
visible mutations, shared aliases, ordinary copies, whole-record assignment, later
argument mutation, two element specializations, rejection diagnostics, reversed
workers and invalid joins, one body increment, and a separate full build plus one
passing-mode declaration edit. The existing
[provider proof](../../tests/integration/runtime_record_borrows.php) also
forwards incoming source references into metadata-defined runtime calls, including
native address-identity checks.

This completes the first [source list prerequisite](source_list_plan.md). Typed element
storage, allocation ownership, source list composition and its bounded copying/
assignment proofs are now implemented through these shared reference contracts.

## Owned-result extension

Supported const opaque source references now use the same borrowed-parameter path.
Owned record/runtime results use separate caller storage; copying a borrowed input
preserves its original owner. Opaque runtime result temporaries may be borrowed through a call and are destroyed
at the full-expression boundary. Temporary record borrowing remains unsupported. See the
[owned-result contract](owned_source_results_plan.md). Owned parameters remain deferred.

# Owned source-function results
Doc Status: supporting

Status: the approved coordinated migration is implemented for bounded owned source
results. Owned parameters and general movement remain deferred. This is the result
portion of step 7 of the [foundations sequence](../planning/compiler_foundations.md#accepted-remaining-implementation-sequence).

## Agreed semantics

The caller provides correctly sized/aligned, uninitialized result storage. The
callee establishes a live result there before normal return; the caller then owns
its cleanup. This does not require a separate heap allocation. A discarded result
still has an owner and follows ordinary full-expression cleanup.

Keep result ownership, construction selection and physical passing distinct:

| Return source | Agreed handling |
|---|---|
| Same-type freshly produced owned value, such as another call result | Forward the result destination where the producing operation can construct there. Do not introduce a fictional intermediate object. |
| Eligible owned named local | Construct a separate result using the applicable construction contract, then destroy the local. Optional named return value optimization (NRVO) is deferred. |
| Borrowed value | Returning an owned copy requires copy permission. Borrowing does not authorize taking resources or returning an escaping reference. |
| Integer or void | Preserve the existing direct-result path. |

Normal-return order: establish the result, end return-expression temporaries, then
clean up remaining locals in scope-exit order. A source used for move construction
remains a live object until its destruction. Do not infer that it is empty or has
no cleanup obligation. Fatal bridge failure retains the agreed policy; recoverable
exceptions and guaranteed failure cleanup remain deferred.

The configured S2S generator's `Generator::renderReturnExpr()` preserves an ordinary
same-type local return, leaving native construction selection to C++. The prior
[source-boundary investigation](lifecycle_contracts.md#generated-source-boundary-observations)
also observed a returned source structure. Follow the C++ rules for
[return sequencing](https://eel.is/c++draft/stmt.return) and
[optional elision](https://eel.is/c++draft/class.copy.elision), without promising to
match Clang's optional NRVO decisions.

## Construction contracts and ownership

Runtime implementations remain Clang-prepared operations imported through the
adapter. Source implementations remain complete compiler-owned lifecycle plans.
Mixed records call their fields' accepted operations; no native bridge duplicates
source lifecycle behavior.

The existing `move_constructible` fact is not an executable operation or an ownership
postcondition. C++ defines it by construction from `T&&`, which may select a copy
constructor. Preparation must compile and validate an actual bridge for the selected
source category; it must not infer resource transfer from that boolean.
[Trait definition](https://eel.is/c++draft/meta.unary.prop).

For source structures, the agreed prototype-extension policy is:

| User-declared lifecycle | Automatic move declaration |
|---|---|
| None | Eligible, subject to fields |
| Default constructor only | Eligible, subject to fields |
| Destructor, copy constructor or copy assignment | Suppressed; applicable valid copy construction remains usable |

These rules follow [C++ copy/move construction](https://eel.is/c++draft/class.copy.ctor).
Custom struct lifecycle syntax remains our documented extension; upstream does not
currently support those struct methods. Compiler-composed field cleanup is not a
user-declared destructor and must not suppress automatic move construction.

Automatic member construction uses each field's applicable contract and may copy
where appropriate. A custom owning source list therefore uses its existing custom
copy constructor for a named-local return. Its allocation descriptor must never be
shallow-copied or transferred merely because its enclosing local is leaving scope.

Unavailable language operations and missing compiler/provider implementations are
different facts. Unsupported required behavior diagnoses; it must not silently
fall back to copying when the source contract would reject or select another operation.
Imported C++ overload/deletion details belong in Clang preparation, not a new C++
overload resolver in the compiler.

The [default generic baseline](generic_type_contract.md) still guarantees an owned
copy, not a move. Definition checking retains that permission and concrete binding
must not add a move requirement. A generic return authorized as copying must keep
that meaning; any future substitution of resource-taking behavior needs a separately
justified permission rule. This slice does not expand generic constraints.

## Migration rationale

Before this migration, source signatures/results were scalar or void. `Callable_Preparer` obtained
caller-storage mode only from imported ABI; body checking, lifetime return consumption
and LLVM return emission assumed a scalar result. The lifecycle model lacked an
operation for construction from an expiring owned source.

Adding only a return-emitter branch would have left checking, cleanup and prepared
ABI describing different programs. The migration extended their shared contracts
together and moved existing imported owned-result handling onto the same
result-destination model. Source/runtime implementation owners and their distinct
ABI validation remain explicit. The checkpoints below validate this common path.

## Implemented owners

| Checkpoint | Existing owners | Change and acceptance |
|---|---|---|
| 1. Normalize construction and result contracts | `type_model/data/definitions.php`, `data/lifecycle.php`, semantic call/signature records; `resolve_types/source_lifecycle.php`, `lifecycle_composition.php` | Represent applicable construction from an expiring owned source separately from copying and assignment. Preserve declaration provenance for implicit-move suppression. Give source and imported results a common owned-result meaning, independent of their ABI origin. |
| 2. Prepare native operations | `src-runtime-preparation/definitions.php`, `bridge.php`, `metadata.php`, family catalog/request owners; `load_runtime/handlers/lifecycle.php` and family acceptance | Expose executable lifecycle operations through definitions and typed contracts. Validate same-type source/destination, source mutability, physical ABI, dependencies and package coverage. Ordinary and family imports use the same path. |
| 3. Check and analyze returns | `resolve_types/signatures.php` and signature joins; `check_templates`; `check_bodies/handlers/statements.php` and result records; `analyze_lifetimes` value/local and ownership workers/joins | Select a checked return-construction plan from source category, declared permissions and destination type. Establish result ownership separately from local cleanup. Preserve borrowed-source ownership and conservative source poststates; do not erase resource obligations. |
| 4. Prepare, lower and emit | `prepare_backend/callable.php`, bindings, ABI utilities and joins; `lower` call/statement/storage contracts; `emit_llvm` function entry, calls, lifecycle and terminators | Prepare one hidden destination ABI for supported owned source results. Bind incoming result storage without treating it as a default-constructed local. Construct/forward before cleanup and return physically void. Reuse imported caller-storage calls and destination initialization; do not invent object byte returns. |
| 5. Integrate and consolidate | Existing coordinator selections, dependencies, exports and fixtures | Prove real source-to-native behavior, private workers, complete joins, fixed inputs and one body increment. Update navigation and support claims after evidence, not ahead of it. |

Record/class names for new rows should follow the concept discovered in their owning
process, not this table's wording. Prefer compact explicit variants; no generic
lifecycle engine or separate source-return pipeline.

## Retained contracts and execution path

- `Type_Store::intern_signature()` records `result_production`; `Result_Contracts`
  classifies concrete results. Source and imported consumers read the same signature.
- `Statement_Checking::return_construction()` selects `return_kind`: scalar value,
  aggregate store, direct construction, copying or construction from an expiring
  local. Bound formal return annotations preserve the default generic copy permission.
- `lifetime_contract::expiring` distinguishes primitive construction, legal copy
  fallback, an executable operation and unavailable preparation. `Lifecycle_Composition`
  selects source member plans; native definitions explicitly request `move_construct`.
- `Allocation_Flow` reports owned result field poststates separately from parameter
  transitions. `Ownership_Preparation` selects callee/construction dependencies;
  `Ownership_Join` requires complete result-path coverage. The bounded result contract
  requires each resource field to have a consistent empty or owned state across normal
  returns. Path-dependent result states and recursive ownership summaries remain
  unsupported, with diagnostics; this adds no Cartesian state tracking.
- `Statement_Lifetimes` consumes `return_construct`; directly forwarded storage has
  no callee temporary cleanup. The source of copy/move construction stays live and
  receives its normal cleanup. Analysis and its result validator both enforce this.
- `Callable_Contract::result_passing()` prepares a hidden destination pointer with
  physical `void` return. `storage_slot::incoming_result` identifies that destination
  independently of borrowed parameters or local allocations. `write_result()` runs
  before cleanup, reusing the same call destination and construction operations as
  local initialization. Incoming semantic parameters account for the hidden pointer.
- Source const borrowing accepts supported opaque inline objects as well as records.
  Owned parameters and mutable opaque source references remain unsupported.
  Record arguments still borrow existing locals or static subobjects; borrowing
  temporary records, including resource-owning records, requires later work. Ordinary
  opaque runtime temporaries without compiler-tracked field resources are supported.

No source-type name, family name or runtime-type name selects compiler behavior.
Result stores use measured layouts; construction/destruction uses accepted operations.

## Execution evidence

[Owned-result integration](../../tests/integration/owned_results.php)
checks two configured native types, native movement versus legal copy fallback,
plain/nested/managed source records, custom-default eligibility, destructor suppression,
borrowing, generic copy permission, early returns, discarded results and missing
preparation. External O1 preserves the lifecycle trace. Reversed body workers, incomplete joins and one body edit preserve fixed
inputs and prepared ABI/layouts. Its nested scopes also cover scope exits at block entry.

[Growing-list integration](../../tests/integration/growing_list.php) returns
and forwards a custom-copy owning list, copies from a borrow, verifies independent
allocations and complete release, and changes one producer body incrementally. A returned enclosing record also proves
automatic movement that copies its custom-copy owning field.
Reordered ownership workers retain equal summaries; incomplete/invalid result field
summaries reject before acceptance.

[String/console integration](../../tests/integration/runtime_console.php)
returns a real runtime string through source functions and forwards caller storage,
including a literal result. Ordinary execution and external full/ThinLTO links exercise
this path. [Family integration](../../tests/integration/provider_family_types.php)
prepares construction from an expiring native-family local through the same bridge.
[Noncopyable inline integration](../../tests/integration/runtime_inline.php)
proves fresh-result forwarding needs neither copy nor move support.

## Acceptance checklist

- A source function forwards an imported owned result into its caller's destination;
  another source function forwards that result again. Retain scalar/void behavior.
- Return an eligible owned runtime local through its prepared construction operation;
  use a second configured type to disprove string/provider-name dispatch.
- Return a plain/nested source record and a managed-field record through automatic
  composition. Generated field cleanup alone must not suppress automatic movement.
- Return a source record with custom copy/destruction and a custom owning-list value;
  prove independent ownership and exactly-once cleanup. A custom default constructor
  alone must not select the suppression rule.
- Return an owned copy from a permitted borrow without changing the original owner's
  lifetime. Default-generic copies retain their definition-level permission.
- Early returns leave the result usable while other live values are destroyed in
  order. Discarded results are destroyed once. Missing construction/ABI support
  rejects before output publication.
- Reordered private results join deterministically; incomplete/stale results reject.
  A full build followed by one body edit replaces affected work, preserves prior
  snapshots and reuses unchanged layout/native packages and lifecycle contracts.
- Compare unclear move/copy selection with focused C++ witnesses. Exercise ordinary
  native execution and external O1/full/ThinLTO compatibility without claiming a new
  compiler mode. Run targeted checks during development, then one ten-worker full
  suite at consolidation; repeat only for new changes or unresolved failures.

The main risks are double destruction, cleanup before result construction, confusing
missing support with legal copy fallback, granting concrete-only generic permissions,
and losing ownership facts at return joins. The proofs above target these directly.

## Consolidation checkpoint

The final full compiler suite passed all 100 fixtures with ten workers in 94.7
seconds. PHP syntax, changed-file brace/doc-comment and whitespace checks passed.
Review covered construction selection, caller destinations, cleanup order, resource
summary acceptance and retained identities. Stale annotation/result restrictions
and call maps were corrected; caller-storage diagnostics now describe both records
and opaque objects. No broader model refactor was required by this review.

The growing-list reuse assertion now reports each invariant separately. An earlier
run overlapped an edit to fingerprinted backend policy and correctly invalidated
layout reuse. An isolated rerun, ten concurrent isolated repeats and the final
unchanged-code full run passed. Future validation runs must keep policy inputs
fixed across each full-build/increment proof.

## Deferred scope

Optional NRVO; user-written move constructors; general move expressions and move
assignment; owned source parameters unless a later agreed proof requires them;
escaping references; new smart-pointer integrations; managed dynamic elements and
nested native family arguments; general C++ aggregate ABI; exception unwinding;
repeated-increment recovery and actual threading. These remain explicit later work,
not reasons to build speculative machinery into the result boundary.

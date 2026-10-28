# Runtime-core callable signature and publication boundary
Doc Status: planning

Checkpoint: 2026-10-28, implementation baseline `5470ea4b0`.
Owner: runtime-core completion batch F, using the existing metadata/type/operation
emitter and sealed publication path. This document is an audit and implementation
proposal, not new language admission.

## Authorities and observed implementation

- `specs/language/closures.md`: exact callable signatures, explicit erasure,
  capture/lifetime rules, reference and variadic inputs, same-origin return identity,
  callable storage/Results, presence/reset and invocation sequencing.
- `specs/instance_methods_v0_2.md` and `specs/native_reference_safety.md`: stable
  reference inputs and same-origin reference returns; receiver retention is separate.
- `runtime/include/scpp/callable_t.hpp`: native signature-specialized storage wraps
  `std::function`, preserving task-transfer permissions across copy/move. The compiler
  supplies capture permissions; ordinary signature identity does not determine them.
- `runtime/include/scpp/collections.hpp`: one policy-driven map/filter implementation,
  typed and boxed carriers, copied callback values, no mutable-reference inputs or
  reference/void outputs. Filters require native `bool_t` exactly.
- `runtime/metadata/modules/core.json`: twelve registered collection overloads:
  collection map/filter for vector/hash/mixed/dynamic, sequence map/filter for vector,
  and keyed map/filter for hash. All twelve use one by-value callback input.

At the F0 baseline there was no callable public recipe. Existing type naming accepts registered
leaf identities and fixed-arity template applications; it does not describe the full
source callable signature. Treating each arity as a new runtime family would multiply
recipes and still lose input modes, variadic shape and return-origin identity.

## Requirements that must stay distinct

| Concern | Required behavior |
| --- | --- |
| Identity | Exact ordered types, input modes/read-only status, variadic marker, return mode and supplying parameter position. Parameter names do not distinguish signatures. |
| Variadics | Source `T...` packs an owned vector, but remains distinct from a single vector parameter even where native storage matches. |
| Invocation | Snapshot the selected callable before argument effects. Detect callable absence after evaluating arguments. Do not reuse method `retain_present()` ordering. |
| Captures | Copy environments with their source value semantics; shared captured objects retain identity. Reject direct/transitive borrowed captures at erased boundaries. |
| Presence/reset | Conditions/isset/negation observe presence without copying or invoking. Reset produces absent storage; empty/count/null comparisons do not become admitted. |
| References | Exact stable caller storage for fixed refs. Same-origin result selects its declared parameter and does not depend on callable lifetime. No interior/captured-origin escape. |
| Containers | Callable elements in vector/hash preserve exact signature and environment copy semantics. |
| Results | Structured/falseable/boolable Results admit exact typed callable payloads. A successful absent payload is still a value state; presence, condition and lazy selection differ. |
| Rejected wrappers | Nullable and ownership-wrapped callables remain rejected; mixed/dynamic callable payloads remain deferred. Physical composition is not source admission. |
| Transfer | Preserve native permission state supplied for the capture environment. Core work must not erase it merely because task-module migration is excluded. |
| Errors | Preserve native bad_function_call for absence; do not invent structured callable errors as part of this migration. Callback exceptions propagate with owned cleanup. |

## Proposed coherent representation

Introduce a prepared **signature descriptor** using the existing operation-signature
vocabulary: registered return storage, ordered parameter storage/passing, optional
variadic marker and optional same-origin result position. Source preparation owns
admission, capture constraints and semantic identity. The C++ metadata tool must
canonicalize physical signature descriptors deterministically, including semantic
shape that is not visible in the native function type. Runtime and application
specializations must therefore agree on identity without ad-hoc aliases.

A descriptor is not a new ordinary storage type or permission to use references as
arbitrary source template arguments. Family metadata declares the signature role.
The callable recipe uses that descriptor to select native function storage and its
concrete invocation method. Reuse the operation formatter, passing modes, D8 return
binding, dependency discovery, inline layout probing and class lifecycle emitter.
No callable-name switches, per-arity emitter or separate publication registry.

Before implementing, settle the concrete descriptor schema and dependency ownership
inside the generation layer. In particular, signature types may themselves contain
prelowered callable/container types. Their dependencies must use the existing type
materialization path, and source formation facts must survive publication reuse.
Do not conceal unresolved signature work by introducing a permanent unary-only family.

Owned concrete closure construction should use the existing application/function
metadata path plus a runtime-owned conversion recipe. Public headers expose neither
std::function nor native templates. The conversion must carry compiler-established
capture permissions rather than guessing them from signature types or dropping them.
No extension of task-module functionality or shared semantic compiler extraction is
part of this boundary.

## Implementation and proof sequence

1. Add/validate descriptors and deterministic identity, including distinguishable
   value/ref/const-ref/variadic and return-origin cases, with metadata-only negatives.
   Reuse ordinary signature/type/operation owners rather than add another code model.
2. Prove one scalar value callback and one owned environment through generation,
   construction, copy/move/reset, invocation, exceptions, capture destruction and
   sealed application reuse. Measure allocation counts and public compile cost.
3. Extend the same descriptor path to void, multiple inputs, references, variadics,
   nested callable inputs and same-origin results. Verify absent-call argument effects
   and callback snapshot behavior. Source borrowed-environment rejection needs prepared
   evidence even if native std::function would physically accept the closure.
4. Batch the twelve registered collection overloads through existing native policies.
   Typed filters need public-boolean to native-bool_t adaptation; boxed operations need
   owned public/native payload conversion. Do not duplicate iteration or key policy.
   Check key/order preservation, empty/scalar errors, callback exceptions, mutation
   behavior and no hidden environment copy per element.
5. Integrate callable presence and Result state/selection paths with C, including a
   success containing an absent callable. Cover typed-container callback storage.
   Audit every requirement above before marking F complete.

Full frontend adoption remains excluded. Prepared fixtures must be labeled as such;
a public C++ consumer does not prove source eligibility or source evaluation lowering.
This audit adds no implementation coverage and intentionally runs no C++ compiles.


## F1 implementation checkpoint

`resolve_signature` now performs canonical shape normalization and supplies ordinary
operation signatures. Registered type aliases/defaults use the existing naming owner;
input modes, variadic marker and return origin remain in structural identity. The
source-provided variadic type is its lowered pack storage. Prepared source bindings
cover return and input storage, with the exact request preserving mode/origin facts.
No callback class, capture factory or collection API is claimed yet. The next step
must make signature-driven class methods and native signature storage consume this
same descriptor, rather than introducing an independent signature parser/emitter.


## F2 implementation checkpoint

Canonical signatures can now be declared as non-storage leaves, normalized and
published through the existing inventory. Source generation binds their return/input
storage facts. Local/imported descriptors normalize in C++ before comparison; the
publication driver supplies imported leaves without performing semantic normalization.
Fake cpp types and storage use of signatures reject. Callable recipe parameter roles,
native function-type formation and invocation expansion are still the next boundary.


## F3 implementation checkpoint

The callable recipe now consumes canonical signature parameters through generic family
roles, derives its private function type from component storage, and expands invocation
through the ordinary operation emitter. Scalar value, reference/const-reference, void
and same-origin returns have compiled release/sanitizer lifetime and allocation proofs.
Owned capture construction currently uses application-function snippets. Capture
conversion as a reusable recipe, nested/variadic composition, container/Result
integration and the twelve collection overloads remain pending. See the completion
inventory for measured costs and the exact bounded proof.


## F4 implementation checkpoint

Owned conversion now has a reusable demanded function-family recipe,
`callable_from<Signature,Closure>`, rather than requiring every application to spell
native callable construction. It copies a concrete closure, preserves prepared transfer
permissions, and rejects malformed permission values before capture copying. Exact
signature/capture admission remains producer-owned. Release/sanitizer proofs cover
capture independence and lifetime, permission states, noncopyable compile rejection
and sealed reuse. Signature composition and collection/Result integration remain next.

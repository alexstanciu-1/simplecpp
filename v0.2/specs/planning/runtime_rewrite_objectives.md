# v0.2 runtime rewrite: architectural purpose and objectives

Doc Status: planning
Date: 2026-10-29
Status: agreed direction recorded from the runtime workspace discussion.

This note preserves the purpose and objectives of the runtime rewrite for future
work. It does not replace normative Simple C++ contracts or claim implementation
completion. Read semantic decisions through the repository's
[authority map](../../../specs/spec_map.md).

## Architectural purpose

That gives the rewrite its architectural purpose: **v0.2 removes runtime complexity
that existed to compensate for the type-blind compiler, while preserving Simple
C++ behavior.**

In v0.1, the S2S generator lacked type information and cross-unit symbol resolution.
The runtime therefore carried adaptation, overload and dispatch machinery that a
full compiler can resolve ahead of execution.

The semantic compiler approach has already been proven in `compiler/my-try/`.
That experience supports a cleaner division of responsibility in v0.2; it does not
mean the new Rust compiler already implements those capabilities. The runtime
stays C++, and this workstream focuses on the runtime rather than compiler development.

## Division of responsibility

| Compiler owns | Runtime owns |
| --- | --- |
| Symbol and type resolution across units | Actual values, storage and lifetimes |
| Operation admission and overload selection | Execution of selected operations |
| Selecting conversions and adaptations | Value-dependent conversion checks |
| Evaluation order and cleanup lowering | Ownership primitives and cleanup execution |
| Selecting concrete container and callable specializations | Container algorithms and callable lifetime |
| Statically provable facts | Checks that depend on runtime data |

Knowing a type does not mean knowing its value. The compiler can select an integer
narrowing conversion, but the runtime must still check representability unless the
compiler proves it. Dynamic data still needs runtime discrimination. Removing
compiler-compensation machinery does not authorize removing necessary runtime checks.

## Rewrite objectives

- **Simplify by correcting responsibility.** Remove compiler-compensation machinery
  rather than merely reorganizing it.
- **Preserve the language contracts.** A simpler implementation must still deliver
  the required Simple C++ semantics. Changes to those semantics need explicit decisions.
- **Reuse proven implementation.** Mine v0.1 for algorithms, module implementations,
  platform integration and useful internal components.
- **Preserve accumulated knowledge.** Carry forward edge cases, failure behavior,
  performance lessons and meaningful tests, even where the implementation is replaced.
- **Adapt at clear boundaries.** Retain useful module internals while replacing
  dependencies on obsolete runtime representations and adaptation machinery.
- **Prove bounded slices.** Each slice should demonstrate correct behavior and a
  clearer ownership model.

For every old runtime component, ask:

> Is this essential runtime behavior, reusable implementation, or machinery
> compensating for missing compiler knowledge?

Module reuse is an explicit objective of the rewrite. Stage module migration after
the necessary core foundations, while reviewing module requirements early enough
to avoid designing a core that cannot support them. Old-tree files remain reference
material; reviewed code must be adapted into the standalone v0.2 implementation.

## Core milestone

The agreed core direction is a standalone C++ runtime with explicit semantics,
concrete generated APIs and predictable ownership. Metadata selects public types
and operations; public include closures remain template-free, while private
templates remain available for implementation reuse.

Core families include scalars, strings, vectors, nullable/result carriers, record
values and ownership handles, typed hashes, mixed and dynamic. Typed hashes and private packed storage are implemented; private mixed ownership
and dynamic shared-container ownership are now proved. Public mixed/dynamic APIs
and traversal remain. Current progression:

1. Completed: custom typed-hash algorithms, concrete lookup/mutation APIs and
   internal ordering proofs; see [validation](../hash_slice_validation.md).
2. Completed: private packed storage forms, bounded key codes and transactional
   transitions; see [validation](../packed_storage_validation.md).
3. Completed private slice: mixed tagged values and COW ownership; see
   [contract](../mixed_storage.md). Public publication and operators remain separate.
4. Completed private slice: independent dynamic-valued containers, shared identity
   and explicit scalar family conversion; see [contract](../dynamic_storage.md).
   Mixed and dynamic remain separate families; public generation is still pending.
5. Integrate traversal lifetime and mutation guarantees before claiming general
   iteration support. Implementation is explicitly deferred under RT-DEBT-01 in the
   [typed-hash batch](runtime_collections_batch.md); review compiler/runtime
   responsibility case by case once the compiler is in place.

Completion should mean that a plain C++ consumer can construct, combine, traverse
and safely mutate the agreed core families through the published API, with
documented failure and ownership behavior. This gives the runtime an acceptance
target independent of compiler progress; it is not a promise of every possible
family combination.

## Decisions still requiring bounded design

- Failure policy for checked casts, invalid indexing, missing keys and empty carrier
  extraction, including the relationship between structured errors and thrown failures.
- Whether to retain or strengthen the current signed-arithmetic overflow boundary.
- Acceptable storage, allocation and copy costs, including string/vector indirection.
- Required composition: nested containers, record/handle elements and move-only
  carrier payloads must be selected explicitly.
- Public traversal, borrowing and mutation/lifetime guarantees.

Compiler development, full legacy API parity, async and speculative combinations
are outside the first core milestone. Module implementation migration is staged
separately, not excluded from the overall runtime rewrite. This note was followed by an explicit sequencing decision: finish the Rust API generator
and retire the C++ generation path before resuming the typed-hash batch.

Validation should cover behavior, public boundaries and lifecycle guarantees;
reused algorithms need focused equivalence evidence. Performance checks should
target relevant costs rather than treating a green functional test as sufficient.

## Related work

- [Runtime assessment and typed-hash batch](runtime_collections_batch.md)
- [Current runtime API summary](../runtime_api_summary.md)
- [Hash/mixed/dynamic port review](hash_mixed_dynamic_port_review.md)
- [Legacy core capability inventory](legacy_core_gap_inventory_2026_10_29.md)
- [Ownership contract](../ownership_slice.md)
- [Rust compiler and API tooling migration](rust_compiler_migration.md)

The first module reuse proof is complete: all eleven native datetime functions,
including host-local formatting/parsing. See [completion evidence](../datetime_local_validation.md).
The [packed-storage batch](runtime_packed_storage_batch.md) is complete, including
key-limit and allocation-failure repairs. Private mixed ownership builds on it.
Preserve the optimized compact pooled-key design; the larger-key proposal was
withdrawn after review of the v0.1 design notes and explicit user correction.

The subsequent `9f73b288c` tooling cutover replaced the Rust API generator with
PHP. New public runtime publication must use that sole PHP path. Private mixed and
dynamic storage now share family-safe mechanics; shared-cycle reclamation remains
RT-DEBT-02 as recorded in the dynamic storage contract.

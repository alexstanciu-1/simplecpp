# Debt: reusable safe low-level concepts

Doc Status: planning
Date: 2026-10-29
Status: open design debt; no implementation or new language semantics authorized.

## Motivation and first case

The compiler's token_sequence and token_buffer currently implement ownership-aware
copy/move assignment, destruction and swap around opaque state pointers. Move
assignment is the first motivating case: replace destination ownership, transfer
source ownership, leave the source in a documented valid state, and release the old
ownership exactly once. Self-move and retained shared snapshots must stay safe.

The broader debt is to make low-level lifetime and resource operations reusable
inside Simple C++ runtime implementations and generated concrete APIs. This is not
primarily a request for new PHP/PHS syntax, nor a blanket ban on private standard
C++ facilities. It must not require every runtime or compiler type to reproduce
raw-pointer ownership bookkeeping.

The [ownership contract review](ownership_contract_review.md) owns the immediate
shared/unique/weak/value proposal and representation alternatives. This note tracks
the general capability beyond that slice; it does not supersede its constraints or
claim its implementation is complete. Ongoing ownership implementation work must
be evaluated separately before closing this debt.

## General-use design direction

Use typed ownership/resource members with correct lifecycle contracts, then prefer
compiler-defaulted special members where they preserve the enclosing invariants.
Generate concrete lifecycle functions when the template-free public boundary or
opaque representation requires them. Private runtime helpers may use templates
and standard-library ownership primitives; public consumers use concrete types.

Do not make one universal move-assignment algorithm for arbitrary types. Types
with cross-member invariants, address-dependent state, fallible cleanup or custom
moved-from requirements need an explicit contract and reviewed implementation.
A memberwise move is safe only when the enclosing invariants survive it.

Responsibilities:
- Metadata: ownership role, requested concrete types and policy selection.
- Generator/pre-lowering: concrete lifecycle emission and validated capability facts.
- Private runtime: actual storage, retain/release/transfer and resource cleanup.
- Compiler: source move admission, sequencing, lifetime restrictions and diagnostics.

Capabilities such as copyability, nonthrowing moves, size and alignment must be
verified against the actual implementation; metadata assertions are not proof.
Const access is not deep immutability, and sharing lifetime is not payload thread
safety. A move does not universally imply an empty source or zero allocations.
Those stronger properties require the selected ownership family's contract.

## Related useful patterns, in proposed priority order

1. Ownership transfer and replacement: move construction/assignment, reset, swap,
   and exchange. Make copy versus transfer explicit; forbid copying unique owners.
2. Scoped resource ownership: concrete RAII handles for files and other resources.
   Define an invalid state, acquisition failure, nonthrowing destructor cleanup,
   and an explicit checked close when callers need cleanup errors.
3. Transactional construction/publication: construct privately, validate, then
   commit through nonthrowing transfer/swap. Failure preserves the prior published
   value. This is a local exception guarantee, not thread-safe atomic publication.
4. Shared immutable snapshots: cheap lifetime sharing, explicit builder/freeze
   protocols and no surviving mutable aliases at publication. Existing const
   handles alone do not prove this property.
5. Borrowed views: bounded byte/element views with owner lifetime and invalidation
   rules. Bounds checks alone cannot prevent dangling views; source support must
   wait for enforceable lifetime constraints or use owning snapshots instead.
6. Scoped cleanup guards: cleanup on early return/failure, with explicit disarm or
   commit. Nonthrowing cleanup and valid captured lifetimes are mandatory.
7. Checked low-level boundaries: validated narrowing, overflow-safe span checks,
   and narrowly scoped resource/native interoperability. Reuse existing checked
   scalar conversions rather than duplicate them.

Opaque inline storage is a representation option, not a safe operation by itself.
If selected, verify size/alignment and object lifetime for each concrete binding;
never byte-copy nontrivial ownership objects. Raw adoption/release, placement
construction and unchecked access remain private unless a separate safe contract
justifies exposing them.

## Completion criteria and bounded next steps

- Prove one concrete ownership family, then adapt a compiler snapshot consumer in
  its own slice; avoid a broad simultaneous rewrite of unrelated runtime types.
- Demonstrate reuse in at least one second consumer before calling the facility
  general-purpose. Keep unrelated pattern additions separately scoped.
- Test replacement, source post-state, self-move policy, last-owner cleanup,
  retained shared readers, failed construction and forbidden operations.
- Verify exception guarantees and noexcept from implementation capabilities.
- Measure wrapper allocations and compile cost; no allocation-free claim without
  a concrete proof distinguishing handle overhead from payload allocation.
- Keep public include closures template-free; generated output stays untracked.
- Record remaining type-specific lifecycle code and why it cannot be derived.
- Do not close this general debt merely because shared/unique wrappers are added;
  explicitly select which patterns are delivered and which remain deferred.

Non-goals: exposing arbitrary raw-memory operations, inventing a new reference-count
engine merely to hide templates, introducing universal user-defined assignment
hooks, or claiming complete static memory safety from runtime checks alone.

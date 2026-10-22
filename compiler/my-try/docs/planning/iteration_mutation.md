# V-D01: mutation during value iteration
Doc Status: planning

## Agreed goal — 2026-10-22

Value iteration is live until the first logical structural mutation or source
replacement. Before committing that change, preserve each active traversal's
remaining shallow key/value entries. Later writes cannot change those preserved
entries. Existing-value writes before preservation remain visible. Nested loops
have independent positions and preservation points. Reference foreach, escaping
element references, concurrent mutation and collection-callback policies are separate.

Preservation failure must leave the mutation uncommitted and every affected cursor
valid; already evaluated source effects are not rolled back. Internal capacity
changes and mixed COW detachment alone are not logical structural mutations.
Independent mixed copies must remain independent; dynamic aliases retain shared
container identity. Destructors must remain nonthrowing.

This is the agreed target, not a claim of complete implementation. The normative
unsupported status in `specs/array_semantics.md` remains until all family and
lifetime proofs land. The vector checkpoint below is an implementation milestone,
not V-D01 closeout.

## Runtime findings and ownership

- `scpp/value_iteration.hpp` delegates to non-owning ranges. Vector traversal uses
  wrapper/index access; table traversal retains raw table/entry positions. There
  is currently no pre-mutation preservation protocol.
- `scpp/vector_t.hpp` owns inline native storage, exposes mutable native storage,
  and declares clear/unset nonthrowing. Structural writes through exposed native
  storage need an explicit audit; observing only wrapper methods is insufficient.
- `scpp/support/hash_t.hpp` owns typed hashes and packed tables. Both mutation and
  whole-value replacement paths must participate, including moves. Copying a
  container must not copy its active traversal registrations.
- `scpp/support/mixed_t.cpp` implements COW detachment through whole-wrapper
  assignment. A cursor must follow its original mixed binding through internal
  detachment without treating that as source replacement. An unrelated mixed copy
  must not preserve that cursor merely because it shared storage earlier.
- `scpp/support/dynamic_value.*` shares table identity but rebinds individual
  wrappers on assignment. Table mutations and source-wrapper replacement therefore
  require distinct notifications.
- `05_backend/cpp/syntax.php::generate_foreach` evaluates the source once and binds
  a const reference. `concrete_iteration.php` transports the native range through
  the compiled boundary. Source lifetime, early exits and the existing nonthrowing
  destruction requirement must survive the new runtime range.

The follow-up source audit found an existing owner-retention path:
`CPP_Declarations::bind_storage_receiver` retains shared field receivers and
extends temporary inline receiver lifetime. Foreach currently calls plain
`expression` instead. Reusing this path is the first candidate for field-source
retention; it does not by itself prove safety when an inline parent is replaced.
That case still needs an explicit runtime lifetime proof, without allocating in
destructors or taking an unconditional collection copy.

Native-storage audit findings so far: `bitset_t::resize` directly resizes its
private word vector, and PHP's internal static-context stack directly pops its
native vector. These are internal sources, not evidence that every source-visible
mutation bypasses wrappers. The remaining audit must distinguish internal scratch
storage, read access and source-visible structural writes; freezing on any mutable
native access would incorrectly change the agreed live-value semantics.

## Delivery and acceptance gates

1. Record bounds-safe runtime observations on dev002. The initial probe checks
   append followed by a future-value write, and replacement by a shorter vector.
   Baseline mode records output without declaring the new semantics supported;
   normal mode requires the agreed preserved output.
2. Establish a vector vertical proof: active cursor registration, staged preservation
   before structural mutation, no-op mutations, source replacement, nested cursors,
   and injected copy failure. All cursor snapshots must prepare successfully before
   publishing any preservation transition. Audit native mutable paths and noexcept
   boundaries before claiming complete vector support.
3. Carry the same protocol through typed hash and packed-table owners, then mixed
   COW and dynamic shared identity. Prove binding replacement separately from
   shared-payload mutation. Avoid retaining extra mixed value ownership merely for
   observation, since that can change COW decisions.
4. Verify strict source programs on dev002 with default prelowering, including
   alias/call mutations, key/value conversion, temporary sources, source-owner
   lifetime, nested loops, break/continue/return and exceptional cleanup. Verify
   retained regeneration and cold/warm builds with application templates forbidden.
5. Run focused ASan/UBSan lifetime checks and compare nonmutating traversal cost
   against the baseline. No unconditional entry snapshot, full operator matrix or
   unrelated runtime redesign is part of this goal. If lifetime or native storage
   exposure requires a wider ownership redesign, discuss that concrete prerequisite
   before implementing it.
6. Update the normative contract, catalog and issue report only to the proven
   boundary. Commit coherent changes and push after each commit. Preserve the
   unrelated edits in catalog `15_blocking_debt.md` and `15_review.md`.

## Evidence

The user authorized V-D01 source/test uploads to dev002 after automatic approval
review initially rejected the transfer. Verification checkout:
`/home/alex-ai/curl/20261020-work`.

Baseline `/home/alex-ai/curl/20261022-iteration-baseline-01` records:

```text
live: 1 20 3
append: 1 20 30
replacement: 1 threw
```

The baseline executable completed with sanitizers; this was observation mode, not
acceptance. Its probe source SHA-256 was
`e89ab66e58448639a341c0c8f310d5eaeb149b7949c910db374a04a373a17e9f`.

### Vector checkpoint

`iteration_preservation.hpp` owns staged cursor preservation. All participating
owners prepare before any cursor commits. A copy failure cancels pending snapshots,
including ones already prepared for other active cursors. `vector_iteration.hpp`
owns live positions, remaining-value copies and source-lifetime state. State is
allocated at first traversal, not ordinary vector construction; wrapper destruction
transfers storage to retained state without allocating or throwing. Copies do not
copy observer registrations. Moves preserve both affected sources before write-back.

Append, successful removal, changed-size resize, clear/unset and whole replacement
now notify vector cursors. Reserve/compact, failed removal and same-size resize do
not preserve. Clear/unset and the corresponding PHP helper declarations can now
propagate snapshot-copy failures instead of terminating through `noexcept`.

Runtime acceptance: `/home/alex-ai/curl/20261022-iteration-vector-03`, using
`tests/iteration_mutation_runtime.py`, passes with ASan/UBSan for the probe and
header-defined vector paths. It covers nested cursors, failure in the second cursor
after the first prepares, subsequent live writes after failure, source destruction,
two-owner move replacement, independent nested-loop starts, nonstructural controls,
remove, clear, resize and unset. The shared runtime object is built normally by
the fixture helper; this is not a fully instrumented runtime-library claim.

Default prelowering: `/home/alex-ai/curl/20261022-iteration-prelower-02`, using
`tests/iteration_mutation_prelower.py`, passes seven source programs: live updates,
append with key snapshots, replacement, mutation through a reference parameter,
copy independence, nested traversal and break cleanup. Each manifest forbids
application templates. All seven cold and warm executables match expected output;
warm builds compile zero units and do not link. The earlier `-01` source run failed
because the new fixture omitted whitespace between `>` and `=`; that fixture typo
was corrected without a compiler change.

Still open: typed hash/packed table and boxed-family integration, native mutable
storage coverage, field/temporary and inline-parent source lifetimes, retained
regeneration, broader cleanup/failure cases, and focused performance measurements.
The optional shared source state changes vector layout and moves may now throw;
review nested-container relocation costs and ownership behavior explicitly before
closeout. Reference foreach remains untouched.

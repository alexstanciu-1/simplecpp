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

Still open after the vector checkpoint: typed hash/packed table and boxed-family integration, native mutable
storage coverage, field/temporary and inline-parent source lifetimes, retained
regeneration, broader cleanup/failure cases, and focused performance measurements.
The optional shared source state changes vector layout and moves may now throw;
review nested-container relocation costs and ownership behavior explicitly before
closeout. Reference foreach remains untouched.

### Direct-table checkpoint

Typed hashes and packed tables now use the same staged observer protocol as
vectors. `table_iteration.hpp` preserves remaining key/value pairs in established
entry order, skipping existing tombstones. Existing-key assignments stay live;
new keys, successful removal, clear and whole replacement preserve first. A
retirement shell is created with traversal state so destruction can transfer
storage without allocation or copying. Cursor registrations remain attached to
their original binding across container copy/move operations.

The packed-table owner supplies this protocol for both mixed and dynamic element
types. This does **not** yet enable the boxed `mixed`/`dynamic` source overloads:
those still use their previous traversal paths until wrapper replacement and
mixed COW rebinding are integrated. Direct `hash<mixed,mixed>` traversal does use
the packed-table protocol. No source-family closeout is claimed from native packed
tests alone.

The first runtime build exposed explicit native instantiations with unique values.
`value_range` now requires copy-constructible payloads (and typed-hash keys), matching
the value-iteration copy requirement instead of breaking noniterable native types.

Dev002 evidence:

- `/home/alex-ai/curl/20261022-iteration-packed-02`: vector, typed-hash and packed-table
  runtime acceptance passes, with probe/header ASan/UBSan. Table cases include
  existing-value writes before preservation, deletion gaps, integer/string keys,
  dense-to-associative conversion, source destruction, removal/clear/replacement,
  and rollback when a later typed-hash cursor copy fails.
- `/home/alex-ai/curl/20261022-iteration-table-prelower-01`: fourteen source programs
  pass with default prelowering and cold/warm execution. Seven typed-hash programs
  cover live writes, insertion, removal, replacement, reference-parameter mutation,
  independent copies and integer keys; the seven vector programs remain controls.

Next: boxed-source binding subscriptions and COW retargeting, followed by source
lifetime/retained and focused performance acceptance. Native mutable vector
storage coverage remains open. Reference iteration and callback policies remain
separate.

### Dynamic binding and boxed-removal checkpoint

`bound_iteration.hpp` adds a binding subscription around the existing table range.
The table subscription observes structural changes through any shared alias; the
binding subscription observes replacement/move of the iterated dynamic variable.
Replacing another alias does not freeze the traversal. Dynamic copy operations do
not copy subscriptions. Move/assignment can propagate preservation failure before
changing either payload; destruction remains nonthrowing. Dynamic layout now also
contains optional binding state and needs inclusion in the final layout/cost review.

Default-prelowering probes exposed two compiler prerequisites:

- The existing conversion bridge admitted required mixed int/string extraction but
  rejected the corresponding prepared dynamic operation. Its existing admission
  check now accepts both boxed families for these two targets, using the same native
  renderer. Exact required-int errors remain distinct from explicit coercive casts.
- Boxed `unset` published a native child pointer as a concrete application pointer.
  The user approved bounded boxed-removal transport. The existing concrete index
  owner now renders native prefix-readiness and removal helpers. Application code
  retains lazy key sequencing and snapshots; each helper re-resolves the prefix
  inside compiled code. No native child pointer crosses the application boundary.
  The helpers retain existing missing-key, null-root and COW behavior. This does not
  claim general boxed presence/emptiness or reference transport support.

Dev002 evidence:

- `20261022-iteration-dynamic-01`: runtime/header-sanitized acceptance passes for
  shared alias mutation, own/other binding replacement, moves, and injected binding
  preservation failure; vector and direct-table controls also pass.
- `20261022-iteration-dynamic-prelower-01`: historical failures at implicit extraction.
  `-02`: five dynamic mutation programs passed after bridge admission; removal
  exposed the native/concrete pointer mismatch. These failures remain recorded.
- `20261022-iteration-dynamic-prelower-03`: eight focused dynamic programs pass,
  including required string extraction, an existing typed loop variable, and
  conversion failure before the next body with finally cleanup and prior value intact.
- `20261022-iteration-boxed-removal-01`: seven removal programs pass, covering nested
  paths, absent/null prefixes skipping later keys, root/prefix replacement during
  key evaluation, and mixed COW removal without iteration.
- `20261022-iteration-dynamic-integrated-01`: all 31 source programs meet expected
  outcomes with default prelowering, cold/warm execution and no warm recompilation
  or relink. Includes two bindings to one dynamic table and shared child identity
  after preservation. One program intentionally exits with a required-int error.
- `20261022-iteration-contracts-01`: existing iteration and indexing PHP contract
  suites pass; the harness also runs the dynamic removal source control.

All directories above are under `/home/alex-ai/curl/`. These are generated-program
proofs, not a native compiler rebuild. Mixed source iteration still uses its old
path and is not covered by the dynamic completion claim. Remaining work includes
mixed COW rebinding, source-owner/temporary lifetime and retained default-prelowering
proofs, the native-vector mutation audit, focused costs, and normative/catalog closeout.

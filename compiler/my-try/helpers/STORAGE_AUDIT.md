# Storage audit and native implementation preparation
Doc Status: historical

Superseded by the [current model](../MODEL.md) and [collection API](STORAGE.md).
Views, parallel AST registries and inline-row lifetime machinery are not current
requirements. The text below records earlier exploration only; references to removed
helpers/tests and “current” behavior describe that historical checkpoint.

Date: 2026-09-24. Scope: current `my-try` PHP implementation, compiler consumers,
helper/model tests and the workspace runtime's typed `hash_t` implementation.
Recommendations below are proposals, not changes to the Storage contract.
The audit itself made no implementation changes. Follow-up items 1–2 are now
implemented: the collector uses append's assigned position; base representation
is private; all five mutation entry points reject reentrancy; replacement hooks
receive the primary key. Guard cleanup is exception-safe. Focused tests cover all
six hooks and both key modes, plus collector IDs after a historical hole.

The remaining preparation is now settled in [STORAGE.md](STORAGE.md) and
[STORAGE_NATIVE_TASK.md](STORAGE_NATIVE_TASK.md): fail-closed write failures,
shared container/record identity, iteration invalidation, reserve/constructor
capacity, nullable position lookup and explicit overflow rejection. PHP behavior
and tests implement the applicable parts. Native layout, performance and converter
work belong to the delivery task. The original audit below is historical rationale;
its descriptions of unguarded hooks and post-failure usability are superseded.

## Subsequent helper split

Storage now owns records without read-only policy or internal_* aliases.
Storage_View_Iface/Storage_View_Abstract/Storage_View separate numeric membership
and read-only access, with storage_append for combined insertion. See
[STORAGE_VIEW.md](STORAGE_VIEW.md) for the current behavior. Historical read-only
Storage proposals below and in earlier issue text are superseded locally.

## Current demand

All 17 concrete Storage properties use numeric mode. Production code appends,
reads by position, iterates, counts and checks emptiness. Whole-stage refreshes
replace Storage objects. String keys, deletion, replacement and subclass indexes
are exercised by helper tests, but not yet by production Storage consumers.
Scope name pools and scalar work lists remain separate array-based indexes.

The current API is sufficient for the running pipeline. Keep `[]`, foreach,
count, is_empty and append returning the assigned position. No general query/ORM
framework, scalar boxing or new ownership policy is needed for this slice.

## Recommended first improvements

### 1. Use assigned positions, not counts, when creating references

`04_analyze/collect/collect.php`, Symbol_Collector::record, assigns local_index
from count(entries) and then appends. This is correct only while entries is dense.
Prefer constructing the entry, appending it with append(), then assigning its
returned position to local_index before updating the work-list indexes. The base
Storage currently has no hooks depending on this field; if such hooks are added,
use the hook's supplied position instead of assuming the record already has an ID.

A focused probe produced count=1 but append position=2 after deleting position 0
from a two-row container. This is not a current compilation failure, because the
collector only appends, but it is a latent problem for incremental mutation.

Parser::parse and its token-span/debug loops also assume a dense token sequence.
Keep token lists append-only during construction and unchanged during parsing;
replace a complete file token list when retokenizing. Do not advertise arbitrary
token deletion as supported merely because Storage supports remove().

Count means live rows; it is neither capacity nor the next position. Do not add
a next-position getter merely to let callers predict IDs; append already returns
an authoritative position. Local IDs remain scoped to their owning Storage.

### 2. Strengthen index-hook boundaries

Today before hooks validate; after hooks update indexes and are forbidden to
throw or reenter. This is an explicit limitation, not an unnoticed transactional
guarantee. A probe throwing from after_add left one published row after the error.

Recommended small PHP hardening:

- Guard all mutation entry points against reentrancy, releasing the guard even
  on exception. Reentry from a before hook can otherwise claim the same position.
- Make base data, positions and key mode private after supplying any genuinely
  needed read-only hook access. Current Tagged_Storage tests do not access them.
  Protected arrays currently allow subclasses to bypass every invariant.
- Supply the primary key to replacement hooks too, matching add/remove. This
  avoids a lookup or representation dependence in key-sensitive derived indexes.

Do not claim these changes provide rollback. Native secondary-index insertion
can fail during allocation even after validation succeeds. Before native delivery,
choose a concrete preparation/commit/rollback protocol, or explicitly restrict
indexes to operations with a proven non-throwing commit. Base rows, primary index,
secondary indexes and next-position state must agree after a recoverable failure.
Arbitrary user callbacks cannot be rolled back automatically.

Directly changing an indexed property through a shared object still bypasses
hooks. Replacing with the same already-mutated object cannot recover the old key.
Keep the current new-record replacement discipline explicit and test it; a future
edit transaction would need captured old index keys or an immutable old record.
Unindexed property writes remain valid. No transparent field interception proposed.

### 3. Clarify reference lifetime before choosing native layout

A stable position does not imply a stable memory address. Current PHP callers
share source, AST, scope and occurrence objects across the graph. Replacing or
removing a row changes container membership; an existing object reference still
refers to the old object. Merely storing T inline in a growing native vector does
not establish that contract.

Specify whether Storage<T> holds shared handles, stable arena-backed records, or
another representation with an explicit lifetime rule. Also specify Storage's
own assignment semantics: PHP assignment aliases the same container, while a
new Storage creates an independent container. Do not accidentally turn assignment
into an implicit native container copy. Graph weakrefs/cycle reclamation remain a
separate model decision; this audit does not choose universal shared ownership.

Prove record identity across append/growth, reserve, replacement, removal and
container replacement. Returning a raw writable slot reference would let assignment
bypass index hooks; preserve indexed assignment through explicit lowering or a
carefully defined proxy. Record-field reads/writes and slot replacement are distinct.

## Native performance requirements

### Numeric specialization first

`Storage<T, false>` should have no string keys, hash index or runtime key-mode
branch. Start with an append-friendly representation and measure empty/small
containers: every call, block, function and array literal may own one or more.
Avoid eagerly allocating backing buffers for empty Storage. A small-buffer
optimization is a measurement candidate, not a requirement to increase every
container's inline size.

Add native reserve(expected_total_slots), with capacity distinct from live count
and the historical position limit. Reserve must not create records or consume IDs.
A PHP semantic counterpart may validate and otherwise do nothing, since PHP arrays
do not expose equivalent reservation. Automatic growth should be amortized; avoid
reserving exactly size+1 on every append. Constructor capacity is optional sugar.

Removal leaves holes and never reuses positions within one Storage lifetime.
Native iteration must skip holes in order. Specify overflow rejection before any
mutation and size any compact index by the greatest representable position, not
just live count. Long-lived churn can retain a large position range with few live
rows: measure it, and prefer replacing a model segment when possible. Do not add
silent compaction, ID reuse or a free list that changes the current contract.

### String specialization

PHP's position_of(string) is linear. Updating/removing an existing string-keyed
row also performs this search. This is acceptable for the current PHP reference
implementation and unused in production so far; adding a reverse PHP map is not
an immediate priority given the user's PHP performance policy.

Native string mode should have expected constant-time key-to-position lookup and
constant-time positional access, retaining insertion/position order. A candidate
is ordered row/key slots plus a hash from string to position. Avoid storing the
whole record independently in both representations. String-mode iteration must
retain string keys, including numeric-looking strings; integers always mean
positions. Readding a removed key obtains a new position.

The workspace `runtime/include/scpp/support/hash_t.hpp` already has ordered
values/keys, live markers, a hash index and live count. Its typed class is final,
and positional helpers are private. It is a candidate for shared internal
mechanisms or composition, not direct Storage inheritance or a promise of free
compatibility. Its non-const operator[] inserts on a miss; its const operator[]
returns a default value on a miss. Storage reads must instead throw, so use/check
an appropriate lookup path. Direct references from hash_t also bypass hooks.

Before reuse, prove insertion-failure consistency, physical-position access,
position-width growth and repeated delete/reinsert behavior. Narrow bucket
selection currently depends on hash capacity while stored entries identify
physical positions; churn merits a targeted native proof before relying on it.
This is a source-review concern, not a native failure reproduced by this audit.
No runtime changes are proposed here; the future task must pin its target revision.

## Optional API additions and deliberate deferrals

- A non-throwing find_position(string): ?int may be useful when string indexes
  enter production; it avoids a separate membership check and exception handling
  for an expected miss. Position 0 must remain distinct from absence.
- A named read-only key_at(position) could support derived indexes; add it only
  if needed, or supply the key directly to hooks. Existing private key_at also
  validates offsets and should not simply become the public API unchanged.
- A mutation generation can diagnose iterator invalidation. For now retain the
  documented no-mutation-during-iteration rule; define native invalidation before
  implementation rather than inheriting accidental PHP generator behavior.
- Do not add clear/reset-in-place, rename, bulk transactions, query helpers,
  scalar values or deep copy without a concrete caller and semantics. Fresh
  Storage is already the model's replacement boundary.
- Persistence must preserve positions, holes, next-position state and graph
  identity. array_keys(data) alone cannot reconstruct history, and saving each
  container independently would lose cross-container aliases. Defer a public
  serialization format until model snapshots/recovery are designed.

## Suggested implementation order

1. Adopt assigned-position use and document dense token-list requirements.
2. Harden hooks, decide exception guarantees and add focused invariant tests.
3. Freeze native identity, lifetime, iteration and allocation contracts.
4. Implement and prove numeric Storage first, then string mode and index hooks.
5. Add converter support for Storage<T, bool> annotations and fluent PHP operators
   separately from implementing the runtime helper.

Native tests should cover empty/one/many, reserve/growth, missing and wrong-mode
keys, numeric strings and empty strings, rejected duplicates, hook ordering and
reentrancy, failure injection, holes/reinsert, index-width boundaries, shared
references and replacement. Measure empty-container size, bytes per live row,
append/read/iteration and string lookup/update under both dense and churn loads.
No timing improvement is claimed by this audit.

## Validation performed

- `php compiler/my-try/tests/storage.php`: passed.
- `php compiler/my-try/tests/model.php`: passed.
- Temporary PHP probes confirmed count versus append-position divergence after
  removal and the documented partial publication after a throwing after hook.
- Native runtime inspected read-only; no native Storage implementation or
  performance benchmark was run.

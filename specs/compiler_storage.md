# Compiler-owned Storage and position-based views
Doc Status: normative

Scope: the opt-in native compiler helper in `scpp/compiler.hpp`, under namespace
`scpp::compiler`. This is not a new general language container. It has no PHP/PHS
conversion binding or `runtime.modules` registry entry yet.
Native CMake consumers link the header-only `scpp_compiler` interface target.

## Shared record identity (2026-09-24 direction update)

The latest direction in issue #242 supersedes the initial inline-value contract
in ce1885b9. Public T is the record type: `Storage<node>` automatically uses the
existing `scpp::shared_p<node>` as its record handle. `Storage<shared_p<node>>` is
rejected rather than double wrapped. There is no ownership-policy parameter.
Inline record layouts, bounded borrows and weak row references are deferred.

Append, assign and replacement accept an existing non-null shared record handle.
They preserve its identity, never copy/move T and never allocate another record.
T need not be copyable or movable. Empty/null handles reject before hooks or
membership writes; validation failure does not consume a position or fail the owner.

`read(position)` and string-mode `read(key)` return a shared handle, as do view
reads and iteration. A handle copy aliases the same record; it is not an independent
snapshot. The former public `snapshot` API is removed. No generic record clone or
deep-copy API is promised. `owner.read(p)->field = value` and
`view.read(v)->field = value` edit the same record, including through read-only views.
The `field`/`set_field` conveniences remain: field copies the selected field value,
while set_field edits the shared record through the guarded owner API.

Growth, reserve and physical handle-slot relocation do not move records or invalidate
retrieved handles. Replacement selects a new record for future reads of that
position, including view reads; old handles continue referring to the old record.
Removal drops the owner's handle and membership; previously retrieved handles keep
the record alive until the last owning handle is released. Dangling view membership
still throws when read and never retargets after a primary key is reinserted.
A failed owner cannot revoke previously retrieved record handles.

Native owner/view objects themselves cannot be copied or moved. Their identity is
aliased through shared owner handles (`std::shared_ptr` for backing owners in this
native slice). Views retain one strong owner handle and contain owner positions,
not per-membership record handles. Record ownership and backing-owner ownership are
separate; source owner-handle binding is still deferred.

Shared records do not solve ownership cycles. A stored record containing a view of
its own owner can form a cycle. The initial compiler port relies on deliberate
lifecycle cleanup/code discipline; this helper makes no bounded-memory claim for
the complete compiler graph and adds no automatic cycle collection or weak lowering.

No native reference/pointer to a container's handle slot is exported. Access to the
record through its shared handle follows the existing runtime lifetime rules.
Indexed fields require replacement with a new record so hooks receive the old index
keys. Direct edits through retained handles bypass collection guards and hooks;
callers must obey the before-hook purity and indexed-field rules. Shared ownership
provides lifetime, not transaction isolation or rollback of record edits.

## Owner positions and mutation protocol

Positions are signed 64-bit, monotonic, start at zero and are never reused.
INT64_MAX is the exhausted next-position state. Checked native allocation limits
may reject sooner. Removal releases the stored handle and leaves an absent public
position. Replacing requires an existing position. Reads never insert. Negative
positions are absent, not coerced to unsigned indexes. Missing reads/removes throw;
unset of an absent position is a no-op. `find_position` returns optional absence
as a lookup result only; optional/null slots are not used in the record buffer.

Record handles are physically dense; the pointed-to records need not be contiguous.
A directory maps historical public positions to
current physical rows, and a reverse directory permits moving the last physical
row into a removed row's space. This is physical relocation, not public-position
compaction. Iteration follows the historical directory and skips absent positions.
Record destruction occurs when its last shared handle is released; historical
position metadata stays.

`reserve(total_slots)` does not shrink or change membership. Append has geometric
growth; prepared numeric commits use non-throwing handle copies/moves. Capacity allocation and
handle/key preparation occur before writes, and may leave larger capacities on
failure but no logical changes. Six before/after hooks receive position, primary
key, and candidate/old shared handles. Hook arguments refer to call-local handle
copies that preserve record identity, not references to interior handle slots.
Before hooks may read but must not mutate records, indexes
or external state. A before rejection leaves the owner usable without consuming
a position. Escaping after-hook errors permanently fail the owner, preserve the
original exception and release the guard. Derived index queries must call
`assert_usable()`.

Every collection mutation, including guarded set_field calls, reserve and absent
unset, rejects
same-owner reentrancy. All subsequent owner operations reject failed state.
`for_each` provides the primary key and a shared record handle, and rejects
structural mutation or guarded set_field calls on that same collection during
traversal. Direct unindexed record edits through shared handles remain possible.
Reads during hooks are allowed.
Destructors must remain non-throwing.

## Primary key modes

The owner uses one mutation implementation and a private compile-time primary-key
policy. Numeric mode has an empty policy (no string slots, hash buckets or mode
flag). It uses its positions as primary keys. String mode adds an unordered
string-to-position index and dense positional key storage; primary keys are
externally supplied and independent of record fields.

`append(value, optional_string_key)` requires a unique explicit key in string mode
and rejects a supplied key in numeric mode. Empty, numeric-looking and embedded-NUL
strings retain their exact byte identity. Duplicate append rejects before hooks.
`assign(string_key, value)` is the native keyed-write adapter: it appends a missing
key or replaces an existing row at the same position through the same guarded
mutation and hooks. Numeric mode rejects it. No native [] adapter is added yet.

`contains`, `read`, `field`, `set_field` and `unset` accept positions in both
modes, and string primary keys in string mode. `replace`/`remove` are positional.
`find_position`/`position_of` accept only the selected primary-key family, so an
integer passed to either lookup in string mode throws invalid_argument. The native
`require_position` helper explicitly validates a position independently of key
mode; this is the path used by view membership validation.

Removing a string key drops both its row and key index entry. Reinsertion receives
a fresh position, including when live count is small after long-running churn.
Index values are full `storage_position` integers, not compact indexes selected
from live count. Iteration emits positions in numeric mode and string keys in
string mode, always in surviving public-position order. Hooks use a signed position
key in numeric mode and `const std::string&` referring to a local copy in string
mode. Replacement preserves the key even if record fields change.

String-key capacity preparation grows record slots, key slots and hash buckets
before hooks, preserving logical state on failure. Hash node/key allocation during
commit is inside the guarded write phase: an exception fails the owner closed,
even if that particular insertion made no visible change. Partial record/index
state is never exposed as usable. Explicit reserve never shrinks hash buckets.
The implementation uses std::unordered_map rather than widening the hash_t repair
scope; it currently duplicates key bytes between positional keys and the index.

## Numeric view

`Storage_View<T, ReadOnly=true>` is final. Its reusable native implementation is
`Storage_View_Abstract<T, ReadOnly>`. Both owner modes are supported without a
key-mode argument on the view. A private variant holds one shared pointer to the
selected Storage specialization; access visits that pointer and owner hooks retain
virtual dispatch. This dispatch belongs to views, not the numeric owner's layout.
Unbacked computed views and the full source interface remain deferred.
Default missing backing rejects access, including empty/count/iteration.

View positions have the same monotonic/no-reuse rules as owner positions.
`storage_position_at(view_position)` resolves membership to an owner position.
Duplicate memberships are allowed. There is no automatic mirroring of owners.
Count/contains describe memberships, including dangling targets. Owner replacement
changes subsequent view reads; owner removal makes dereference throw while the
membership remains removable or redirectable. Owner failure rejects all backed
view access, including metadata reads.

Ordinary append/replace/remove/unset reject when ReadOnly is true. Internal
variants bypass only this policy. They validate the target before modifying
membership and never remove or replace owner records. Field writes and reserve
remain allowed on read-only views. Both owner and view enforce their own guards.

`storage_append(record, optional_owner_key)` prepares view space, appends through owner hooks and adds
one membership, returning the OWNER position. Owner validation/preparation failure
leaves the view usable; owner write failure makes backed access reject. Once owner
append succeeds, any membership-write error permanently fails only that view and
leaves the owned row intact. Other views of a healthy owner remain usable.
An explicit owner key is required for string backing and rejected for numeric
backing. View membership still accepts owner positions, never primary string keys.

Protected membership primitives support failure/index-maintenance tests. A subclass
must delegate to the base primitive to keep this slice's membership representation;
alternative virtual representations are outside this slice.

## Boundaries and cost

There is no clone/reset, source []/foreach adapter, generic ownership
policy, weak backlink machinery, persistence, whole-AST migration or claim of
whole-compiler convertibility. Native reads now preserve shared record identity,
but this does not implement
source construction/indexing/iteration bindings or prove whole-compiler conversion.

Numeric access and removal are O(1), append amortized O(1). Iteration is O(historical
positions), including after heavy deletion. Live record handles occupy a dense vector;
each live row also has a reverse position and each historical position a native
row index. Automatic growth tracks live-record and historical-directory extents
independently; historical churn does not grow unused handle capacity. Capacities
remain allocated after deletion. This favors a straightforward
compiler-lifetime implementation; no measured performance advantage is claimed.
String primary-key lookup/update is expected O(1) plus key hashing/copy costs;
string mode has additional key buffers and hash node allocations. Positional reads
still use direct directory lookup. Key-slot growth also tracks live and historical
extents separately. These costs need measurement before optimizing representations.

Native proofs: `tests/runtime/compiler/level_01/runtime_compiler_001_storage.cpp`,
`runtime_compiler_002_string_storage.cpp` and `runtime_compiler_003_shared_records.cpp`
in the same folder. The shared-record fixture uses noncopyable/nonmovable records
and checks identity, null rejection, retained lifetime and final destruction.
Build/run instructions and remaining delivery work are in
`specs/planning/compiler_storage_native_slice.md`.

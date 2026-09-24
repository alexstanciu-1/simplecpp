# Compiler-owned Storage and position-based views
Doc Status: normative

Scope: the opt-in native compiler helper in `scpp/compiler.hpp`, under namespace
`scpp::compiler`. This is not a new general language container. It has no PHP/PHS
conversion binding or `runtime.modules` registry entry yet.
Native CMake consumers link the header-only `scpp_compiler` interface target.

## Agreed ownership revision

Issue #242 separates owning Storage from position-based Storage_View. The user
subsequently approved a compiler-specific initial implementation with explicit
element types, actual values rather than nullable record slots, and relationships
expressed as owner-local positions. This supersedes the issue's blanket guarantee
that every retrieved record independently survives removal/replacement for this
native slice. The PHP helper still has PHP object identity semantics.

`Storage<T, UseStringKey=false>` stores exactly T. It adds no per-record shared pointer. The
initial native implementation requires copy-constructible T with non-throwing
move construction, move assignment and destruction. This is a bounded first
implementation, not a promise to support every compiler record type already.
Snapshots preserve T's own copy semantics; value records are copied, explicit
shared handles share their pointee. No snapshot is a write-back alias.

Native owner/view objects cannot be copied or moved. Their identity is aliased
through shared owner handles (`std::shared_ptr` in this standalone native slice).
Views retain one strong owner handle, and contain numeric owner positions, not
record copies or per-membership pointers. Source handle binding is deferred.
Embedding such a view in a record of its own owner can create an ownership cycle.
This slice does not reclaim cycles or claim bounded memory for the complete AST;
actual nested-view ownership needs review during model integration.

No native pointer/reference to an interior row is exported. `snapshot(position)`
returns a value copy; `field(position, &record::field)` returns a field copy.
`set_field(position, &record::field, value)` edits an unindexed field in place.
Indexed fields must use replacement with a new record, preserving old index keys
for hooks. These native member helpers apply to inline records; explicit handle
elements are accessed by copying their handle with snapshot. Fluent source field
access is not implemented by these helpers.

## Owner positions and mutation protocol

Positions are signed 64-bit, monotonic, start at zero and are never reused.
INT64_MAX is the exhausted next-position state. Checked native allocation limits
may reject sooner. Removal destroys the stored value and leaves an absent public
position. Replacing requires an existing position. Reads never insert. Negative
positions are absent, not coerced to unsigned indexes. Missing reads/removes throw;
unset of an absent position is a no-op. `find_position` returns optional absence
as a lookup result only; optional/null slots are not used in the record buffer.

Records are physically dense. A directory maps historical public positions to
current physical rows, and a reverse directory permits moving the last physical
row into a removed row's space. This is physical relocation, not public-position
compaction. Iteration follows the historical directory and skips absent positions.
Record destruction releases any owned payload; historical position metadata stays.

`reserve(total_slots)` does not shrink or change membership. Append has geometric
growth; prepared numeric commits use only non-throwing moves. Capacity allocation and
record-copy preparation occur before writes, and may leave larger capacities on
failure but no logical changes. Six before/after hooks receive position, primary
key, and candidate/old snapshots. Hook arguments refer to call-local snapshots,
not interior storage. Before hooks may read but must not mutate records, indexes
or external state. A before rejection leaves the owner usable without consuming
a position. Escaping after-hook errors permanently fail the owner, preserve the
original exception and release the guard. Derived index queries must call
`assert_usable()`.

Every mutation, including field writes, reserve and absent unset, rejects
same-owner reentrancy. All subsequent owner operations reject failed state.
`for_each` provides position and value snapshots, and rejects structural or field
mutation of that same collection during traversal. Reads during hooks are allowed.
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

`contains`, `snapshot`, `field`, `set_field` and `unset` accept positions in both
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
whole-compiler convertibility. Source conversion must not silently treat snapshots
as the PHP reference's persistent object handles.

Numeric access and removal are O(1), append amortized O(1). Iteration is O(historical
positions), including after heavy deletion. Live records occupy a dense vector;
each live row also has a reverse position and each historical position a native
row index. Automatic growth tracks live-record and historical-directory extents
independently; historical churn does not grow unused record capacity. Capacities
remain allocated after deletion. This favors a straightforward
compiler-lifetime implementation; no measured performance advantage is claimed.
String primary-key lookup/update is expected O(1) plus key hashing/copy costs;
string mode has additional key buffers and hash node allocations. Positional reads
still use direct directory lookup. Key-slot growth also tracks live and historical
extents separately. These costs need measurement before optimizing representations.

Native proofs: `tests/runtime/compiler/level_01/runtime_compiler_001_storage.cpp`
and `runtime_compiler_002_string_storage.cpp` in the same folder.
Build/run instructions and remaining delivery work are in
`specs/planning/compiler_storage_native_slice.md`.

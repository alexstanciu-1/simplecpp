# Private mixed value and COW storage

Doc Status: normative
Scope: private v0.2 ownership foundation; not a published mixed API.

## Ownership boundary

`runtime/src/boxed/mixed_value.hpp/.cpp` owns the private tagged value.
`packed_table<mixed_value>` owns its containers. `scpp_mixed_storage` links the
compiled packed-key pool. The COW rule is mined from the donor's
`mixed_t::detach_shared_table_for_write` at `6e7ee001f`, while the separated-family
amendment in `specs/dynamic_types.md` governs meaning.

Kinds are null, bool, int64, float64, string and mixed container. Scalars preserve
kind; these checked getters are extraction, not numeric/string conversion. Numeric
and boolean payloads are inline and allocate nothing. Full signed-64-bit integer
values remain admitted; the packed integer-key bound is a separate restriction.

Strings use indirect immutable owned bytes. Copying a string value can share those
bytes because no mutable access escapes; `get_string()` returns an independent byte
copy. Embedded NUL is preserved. String assignment replaces the binding. Containers
hold only mixed values. There is no dynamic carrier or cross-family adapter.

Mixed copies share container storage until mutation. A write to shared storage
copies its packed table, including mixed payload values, then mutates the prepared
copy before publishing it. Nested container values detach on their own later writes.
A unique table mutates in place through the packed engine's failure boundary.
No entire tree is eagerly cloned, and no whole-table copy is made on every write.

## Private operations

Factories explicitly select null/default, boolean, integer, floating, string or
container. There are no implicit native constructors. Default is null; explicitly
constructed empty container is a different kind. Moves leave null reusable sources.
Copy/move/self-assignment preserve ownership; `clean()` resets only this binding.

Read returns a copied mixed value; missing entry or null receiver returns null.
`contains` tests membership, including entries whose payload is null; it is not the
source `isset` operation. Reads and absent removal do not detach, allocate a table,
or intern a missing key. Non-container scalar indexed access throws.

Set and append may bootstrap null into a container. The container becomes visible
only after the insertion succeeds. Integer keys are validated before storage
mutation, including on null receivers, using the approved 0…INT32_MAX limit.
String keys are already classified byte strings; source normalization is deferred
to its adapter. Removal preserves remaining identities. Append uses the packed
engine's current-live-maximum rule and checked limit.

Indexed reads never return mutable references. To change a nested extracted value,
modify the copy and explicitly store it into the parent. Source nested-write-path
lowering is not implemented here. Inserting a value into itself captures its prior
value tree because the argument copy forces detachment; it does not create a shared
COW cycle.

## Failure and lifetime

Kind mismatch throws `std::runtime_error`; key and append failures retain the packed
engine's exception types. No new public error ABI is defined. Scalar/string factories
prepare before assignment. Failed allocation while bootstrapping null leaves null;
failed shared-table mutation leaves both bindings' prior contents unchanged. Unique
table failures retain the packed-storage guarantee; capacities may change while
membership/order remain unchanged. Valid interned keys may outlive a failed write.

Default construction, copy, move and reset are nonthrowing. Copy is cheap because
all indirect payloads are shared, with immutable string bytes and table COW. Shared
ownership is not permission for concurrent mutation. Getters returning string copies
can allocate and throw without changing the value.

## Deliberate limits

This slice proves ownership only. It does not publish mixed through Rust metadata or
generated C++ APIs, claim general native shared/weak carrier support, add operators,
casts/coercions, boxed-key dispatch, JSON, public traversal or compiler integration.
Those owners must select explicit operations later; the old type-blind overload
matrix and traversal subscriptions are not imported. Dynamic remains the next
separate family, with shared container identity rather than COW. RT-DEBT-01 remains.

## Proofs

`tests/mixed/storage.cpp` covers allocation-free inline values and copies, exact kind
checks, independent string extraction, null versus empty, lazy probes, key bounds,
COW replacement/removal, nested copy/write-back, self-insertion, clean/move/self-assign,
append limits and failure sweeps through bootstrap/detach/remove/string construction.
Validation results are recorded in [mixed_storage_validation.md](mixed_storage_validation.md).

# Compiler object collections
Doc Status: normative

Scope: the opt-in native compiler module `scpp/compiler.hpp`, namespace
`scpp::compiler`, CMake interface target `scpp_compiler`. This contract replaces
the previous owner/view design for issue #242. Source/converter bindings are
pending; these are currently native C++ helpers, not available PHS declarations.

## Public types and identity

`Storage<T>` is a numeric object list. `Keyed_Storage<T>` is an exact string-keyed
object collection. T is the record class, never `shared_p<T>` itself. Their
`record_handle` is the existing `scpp::shared_p<T>`.

Insertion and replacement accept existing non-null handles. They never copy or
move T, or allocate another record. Reads and iteration copy handles, retaining
the same object. `collection.read(key)->field = value` edits that shared object.
Noncopyable and nonmovable record classes are supported. Previously read handles
survive replacement, removal, growth and collection destruction.

Collection construction creates independent membership. Copy construction and
assignment alias the collection state; move syntax also copies this alias and
leaves the source usable. Distinct collections can contain the same record, and
a collection can contain the same record at multiple keys. There is no implicit
clone, deep copy or cycle collection. Acyclic objects die after their final handle.

Both constructors accept only an optional runtime capacity (default zero).
`reserve(capacity)` is an allocation hint and changes neither membership nor count.
Negative capacity throws `std::invalid_argument`. Native capacity and numeric-key
arguments accept signed integral types; booleans, floats and strings do not match
these methods. Source integer-wrapper adaptation and diagnostics remain pending.

## Numeric collection

- `append(record)` returns a signed 64-bit position starting at zero. Positions
  increase monotonically and are never reused or renumbered, including after all
  records are removed. INT64_MAX is the exhausted next-position state; append
  throws `std::overflow_error` before writing when that boundary is reached.
- `read(position)` returns a required existing record handle.
- `replace(position, record)` requires existing membership; it cannot fill a hole.
- `remove(position)` requires membership. `unset(position)` tolerates absence.
- `contains(position)` tests membership without inserting. Negative positions are absent.
- `count()` counts live members; `is_empty()` tests that count.
- `for_each(callback)` calls `callback(original_position, record_handle)` in
  ascending position order, skipping holes. Both arguments are values.

Absent required membership throws `std::out_of_range`. The native owner is a
shared state containing `vector_t<record_handle>` and a live count. Removal clears
a slot without compacting. Empty internal slots are tombstones, not nullable
application records. Retained slot extent is the next position; deletion does not
reclaim that extent. There is no separate position directory.

## Keyed collection

- `add(key, record)` inserts uniquely; duplicates throw `std::invalid_argument`.
- `set(key, record)` inserts or replaces, the native entry point for eventual
  source keyed assignment. `replace(key, record)` requires existing membership.
- `read`, `remove`, `unset`, `contains`, `count` and `is_empty` behave as above.
- `for_each(callback)` passes copies of the original string key and record handle
  in insertion order. Replacement preserves order; removal/reinsertion goes last.

Native keys accept string/string-view compatible inputs, including string literals;
integer and boolean keys do not match the methods. Empty, numeric-looking and
embedded-NUL strings are exact distinct keys. Use a length-bearing `std::string`
or `std::string_view` for embedded NULs. No integer-position API, append method or
PHP key-prefix encoding exists.

The shared state contains a standard hash index and an insertion-order list. The
index refers only to private list nodes; no interior references escape. The existing
`hash_t` cannot currently supply this wrapper's allocation-failure invariants:
its insertion updates multiple vectors separately. It also chooses index width from
hash capacity while storing historical slot indexes. This module does not repair
or change that core container. The small local ordering structure avoids depending
on those behaviors and can be replaced behind the wrapper later.

## Failure and iteration rules

Null record handles throw `std::invalid_argument`. Validation rejection leaves
membership unchanged. Allocation failures propagate and leave valid destructible
containers with matching index/list membership and counts. The numeric wrapper
uses vector's insertion guarantee; keyed insertion removes its provisional list
node if index insertion throws. There are no hooks, callbacks during mutation,
reentrancy guards or poisoned states.

Membership mutation or reserve during iteration is unsupported, including through
another alias. Record field editing is supported. Iteration exports owning record
handles and value keys, never references to container slots.

There are no views, read-only membership policies, bypass methods, backing-owner
references, secondary-index automation, field/snapshot helpers, serialization,
transactions, concurrency, ownership policies or layout specialization. Dedicated
compiler indexes remain with their process/data owners.

## Source binding boundary

The future source forms are the two concrete types, capacity-only construction,
method calls, numeric `[]` append, indexed reads/writes, `isset`, `unset`, `count`,
`is_empty` and `foreach` with original keys. Numeric assignment maps to `replace`;
keyed assignment maps to `set`; membership queries map to `contains`.

**None of these source/converter bindings are implemented by this native slice.**
Native access currently uses the explicit methods above, without C++ subscript
proxies. `for_each` proves iteration semantics but is not a generated source
`foreach` binding. There is no `runtime.modules` registry entry yet. Issue #242
remains incomplete until these paths are implemented and tested end to end.

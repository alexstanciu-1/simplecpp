# Compiler object collections
Doc Status: normative

Scope: the opt-in native compiler module `scpp/compiler.hpp`, namespace
`scpp::compiler`, CMake interface target `scpp_compiler`. This contract replaces
the previous owner/view design for issue #242. Strict PHS binds these wrappers
through the PHP language adapter. Enable `"compiler"` in `runtime.modules`; this
module is header-only and has no additional native link library.

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
these methods. The same boundaries accept signed `scpp::int_t<Rep>` values;
keyed boundaries accept `scpp::string_t` without losing embedded NUL bytes.

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
- `set(key, record)` inserts or replaces, the native keyed assignment operation. `replace(key, record)` requires existing membership.
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

## Strict PHS source binding

Use the concrete types with a record class name as their single type argument:

```php
class Row {
    public int $value = 0;
    public $children Storage<Row>;
    public $named Keyed_Storage<Row>;
}
class Roots {
    public static $rows Storage<Row> = new Storage<Row>();
}
function retain(Storage<Row> $rows): Storage<Row> { return $rows; }
$capacity int = 16;
$rows Storage<Row> = new Storage<Row>($capacity);
$named Keyed_Storage<Row> = new Keyed_Storage<Row>();
$row Row = new Row();
$position int = $rows->append($row);
$rows[] = $row;
$named->add("name", $row);
$named["another"] = $row;
$rows[0]->value = 7;
$old Row = $rows[0];
$rows[0] = new Row(); // $old still owns the previous record.
foreach ($rows as $position => $record) { $record->value += 1; }
foreach ($named as $key => $record) { $record->value += 1; }
unset($rows[1]);
$live int = count($rows);
$present bool = isset($named["name"]);
```

Generic fields and locals use the existing PHS postfix annotation syntax shown
above (attached type comments are also accepted). Parameters/returns use the
existing typed signature syntax. Fields without initializers construct independent
empty collections, including static fields. Explicit constructors require the
record argument: `new Storage<Row>()` or `new Keyed_Storage<Row>($capacity)`.
No capacity or key-mode template argument exists. Bare `new Storage()` does not
infer T from an assignment target. Do not author `Storage<shared<Row>>`.

Source methods are `append` (numeric), `add` (keyed), `replace`, `remove`,
`reserve`, `is_empty`, and `count`. Numeric append returns source `int`;
`is_empty` returns source `bool`. Keyed assignment inserts/replaces; numeric
assignment requires existing membership. Both support required `[]` reads,
`isset`, `unset`, `count`, `empty`, and by-value `foreach`, with optional keys.
`foreach` values are owning record handles: field edits work without `&`.
By-reference membership iteration is rejected. Iteration mutation remains unsupported.

The generator recognizes only explicitly authored collection types and existing
local declaration/return metadata. It emits the wrappers by value (their shared
state supplies aliasing), required `read`, assignment `assign`, non-throwing
membership `unset`, and the existing `foreach_range` protocol. Key/record validation
stays in native typed operations. It does not infer general program types or
perform inheritance/ownership analysis. Constructor type arguments survive the
host PHP parser through scanner annotations restored before IR construction.

STAN models the concrete methods and iteration/index element/key types. Its
normal pre-build checks remain enabled; unsupported native key representations
are also rejected by C++ constraints. Signed source integer widths are accepted;
unsigned integers require an explicit conversion to the signed position/capacity
domain. `mixed`/dynamic key coercion is not provided.

### Current metadata limit

The generator's class-member metadata remains local to a source file. Direct
fluent access to collection fields (including static roots) declared only in a
different file needs an explicit typed local in the consumer:

```php
$roots Storage<Row> = ExternalRoots::$rows;
$children Storage<Row> = $record->children;
$children[0]->value = 3;
```

These locals alias the original membership. Cross-file declarations, signatures
and linkage work; this slice does not add a project-wide property or return-type
inference catalog. An explicit typed local is likewise needed for a collection
returned by a function whose declaration is outside the current source file.
Executable-PHP portability annotations/conversion and the real PHP compiler rewrite
are outside this slice.

Reproduction and validation commands are in
`specs/planning/compiler_storage_native_slice.md`.

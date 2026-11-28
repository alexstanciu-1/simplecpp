# Native collection traversal

Doc Status: normative
Scope: v0.2 runtime/generator primitives. The [compiler foreach slice](../compiler/docs/foreach.md) consumes this protocol.

## Protocol

Vector, typed hash, mixed and dynamic share generated `iteration_limit()` and
`iteration_next(position, limit, outputs...)` methods. The publication plan records
these as compiler-internal traversal bindings, not source-visible methods.
Public headers expose concrete signatures, with no templates or storage details.

The caller evaluates the source once, keeps its wrapper/containing owner alive,
captures its physical extent, and starts an int64 position at zero:

```cpp
auto limit = values.iteration_limit();
scpp::int64 position = 0, value = 0;
while (values.iteration_next(position, limit, value)) {
    // Body may mutate values; no storage reference survives this boundary.
}
```

There is no cursor object, registered observer, retained table owner or snapshot.
Every advance reloads current storage and checks its current extent. Vector indexes
are physical positions; hashes skip deletion holes. Hash compaction is not added.
The original limit never grows. Each successful step copies the selected element
(and key when requested) before publishing outputs. Copy/allocation failures throw
normally. Supported element moves are nonthrowing. Negative position/limit throws
`std::out_of_range("iteration_bounds")`.

Value-only overloads avoid key materialization. Vector keys are int64; typed hash
keys retain their declared type. Typed hashes also publish iteration_next_key,
which copies only the selected key and commits the cursor after publication.
An internal iteration_current_value operation copies from the slot immediately before
that committed position. The caller must prove the same wrapper and selected entry
remain valid between advance and read. Native bounds and live-slot checks remain in
place. The read exposes no table pointer or reference.
Mixed/dynamic keyed overloads return int64
`key_index` plus string `key_text`: -1 denotes text; nonnegative denotes an integer
key. Integer keys clear key_text. This follows their existing normalized key domain
and does not make dynamic depend on mixed. Text keys preserve all bytes, including
NUL. The compiler will own materialization of a source-language key binding.

## Ownership and mutation

Cursor and output slots must be distinct caller-owned variables, not element
references. The source wrapper must remain alive through each call and body.
A shared owner containing a field must be retained by the caller; an unowned field
address does not extend lifetime. The primitive borrows the wrapper, not its table.
Boxed value output may alias the source wrapper: selection is staged before the
source is replaced, with no subsequent access to its old storage.

Mixed retains normal COW and dynamic retains shared table identity. Traversal adds
no table owner, so it does not itself cause a mixed detach. Current packed versus
associative mode is checked on every boxed advance. Replacing a boxed source with
scalar/null throws `boxed_expected_container`, even after the old limit is reached.
Valid empty typed/boxed containers simply return false.

Across source flavors, traversal of an unmodified typed hash visits live entries in key insertion order. This applies to key-only, value-only and paired traversal. Replacing a value keeps its position. Erasing a key and inserting it again places it last.

Mutation during traversal has no stable visitation promise: entries may shift, disappear, change or
be replaced. Bounds and hole checks prevent invalid slot access; no old entry
sequence is preserved. Independent loops have independent integer positions.
This value traversal protocol excludes concurrent mutation and escaping element
references. These methods do not make arbitrary dangling native references safe.

## PHP writable vector and hash foreach

In a synchronous body, `foreach ($items as &$item)` and
`foreach ($items as $key => &$item)` accept a named writable local or parameter of typed vector or hash type.
Writes through a mutable reference parameter update the caller’s collection;
writes through a by-value parameter update the callee’s copy. An owned variadic pack (such as `int ...$items`) follows
the vector rule, including when the caller unpacks a vector. An empty pack runs
zero iterations. Read-only references and read-only packs are ineligible.
The element name is fresh and scoped to the loop. Each iteration
selects one vector index or live hash entry. Changing the optional key variable leaves the selection intact.

Reading the element reads the current slot. Assignments and supported compound
updates replace it immediately. Reads through another name for the collection see
the completed write, including before a break, return or exception. A by-value
capture takes the current element value. Shared handles retain object identity.
For an inline record, plain assignment to an immediate writable field (for example,
`$point->x = 12;`) updates that field in the selected slot. Earlier value copies retain
their contents. Field visibility, read-only rules and assignment conversions apply.
For deeper inline writes or compound field updates, edit a copy and assign it back.

The collection must keep its size, order and storage throughout the loop. Preparation
rejects append, removal, resize, reserve, reset, replacement and mutable reference
calls on the collection or a lexical alias. A closure with a mutable capture of that
storage cannot be invoked during the loop. By-value and read-only helper calls
remain eligible. A direct synchronous source free function with a value or void result
may take the selected element by exact-type mutable or read-only reference when
its declared parameter supports that mode. The borrow lasts through that invocation.
A read-only parameter observes replacement or mutation through a second parameter
bound to the same slot. Writes remain visible if the helper throws. The same collection protections
apply during argument evaluation and callback re-entry. Indirect
calls, methods, local aliases and segment grants cannot borrow
the selected element. Ordinary indexed writes to existing
vector slots remain visible through the loop binding.

An inferred local PHP closure inside the loop may capture the selected slot with
`use (&$point)`. Creation binds the current slot once. Direct calls of that
local during the iteration read and write the live element under the same access
rules as the loop body. The borrow ends with its lexical scope, including on
continue, break, return or exception. A nested inferred local closure may recapture that binding by reference. It binds
the reference held by its enclosing closure, including after the visible loop key
changes. Each borrowing closure may be called within its lexical scope or passed as a
named local to a direct synchronous callback-view consumer. Storing or returning
either closure is rejected. Nested by-value
recapture of a borrowed binding remains deferred.

A consumer takes a required view by value, for example
`function applyTwice(callback_view<void> $op):void {$op();$op();}`. Its body
may invoke its parameter directly or forward it to another direct synchronous
consumer. A consumer may also declare a required by-value bool before the view,
as in `applyIf(bool $enabled, callback_view<void> $op)`. An `if` or `elseif`
condition must read that parameter directly. Every branch, including `else`,
obeys the same body rules. A consumer forwarding to a two-parameter consumer
passes its own flag and view in their declared slots. Forwarding passes the
existing pointer and invocation function. Preparation checks each consumer in its
defining file and rejects storage, scheduling and
suspension. The caller supplies an inferred local closure with the exact nullary
void signature. Its possible writes are checked as for a direct invocation,
including when the caller supplies a false flag.
The view passes a pointer to the existing closure and a typed invocation function
for the duration of the synchronous call. Each invocation observes the live slot,
and a throw unwinds through the consumer with earlier writes visible.

For a borrowed source, the loop also protects other mutable reference parameters
and by-reference lexical captures of the same canonical collection type. Distinct
names may refer to the same caller slot. Reads through these names observe writes
immediately. Structural mutation resumes after the loop. A by-value parameter
remains independent. A lexical closure with a mutable capture of a protected
slot cannot be invoked during the loop. Typed callback forwarding requires a
capture-free target, and owned callable storage excludes borrowed environments.
Passing a protected slot as a mutable argument to either form is rejected.

A writable hash loop visits live keys in insertion order, including after erasure
and reinsertion. Write through the element binding to replace the selected value.
Preparation rejects separate indexed writes through the hash or an alias, even
when the written key exists. Keyed assignment could otherwise insert a new entry.

## Typed map and filter

The general API exposes six synchronous helpers. C++ can use the general API by
default. Other flavors select it with the project setting `"general_api": true`.

| Operation | Vector input | Typed hash input |
| --- | --- | --- |
| `collection_map(source, op)` | Dense `vector<U>` | `hash<U,K>` with original keys |
| `collection_filter(source, predicate)` | Dense `vector<T>` | `hash<T,K>` with original keys |
| `sequence_map`, `sequence_filter` | Same vector operations | Unsupported |
| `keyed_map`, `keyed_filter` | Unsupported | Same hash operations |

T is the input value type, K the hash key type and U the map callback's result.
The result must be an available runtime or application collection specialization.
For table-valued `mixed` and `dynamic`, the two `collection_*` helpers preserve
the input family and the actual keys in encounter order. Map requires a same-family
callback result. Filter requires bool. Empty input yields a fresh empty table of
the same family. A scalar or null input raises the existing boxed traversal error
before any callback invocation. Copies of nested values follow ordinary mixed
copy-on-write and dynamic shared identity. Fixed-array transforms remain deferred.

Both arguments are evaluated once under ordinary call rules. The helper borrows
the live input and keeps its selected owner alive through traversal. The foreach
bounds and mutation rules above apply. The callback receives one visited value
by value, runs once for each visited entry, and may have ordinary synchronous
side effects. A map stores its returned value. A filter requires a boolean and
keeps the original visited value on success. Hash output retains insertion order
and exact keys. Each result has fresh outer storage.

Named functions, static method references, compatible closures and typed callable
values can supply the callback. The signature requires one exact, required value
parameter and a non-void value result. Inline lambdas can obtain their input type
from the collection and their filter result type from context. An inline map lambda
needs a declared result. Traversal of an empty input returns a typed empty output
without invoking the callable. An absent owned callable fails on invocation under
the ordinary callable rules. An exception propagates and discards the partial result.

## Verification

`tests/collection_iteration.cpp` exercises all concrete vector/hash specializations,
both boxed families, growth, holes, shifting, clear, replacement, moves, nested
positions, packed-to-associative transitions, COW/shared identity, receiver/output
aliasing, embedded NUL keys and retained wrapper lifetime. Run the entire runtime
and this fixture with AddressSanitizer, UndefinedBehaviorSanitizer and leak detection
when changing the protocol. Instrumenting only the fixture would miss storage bugs.

Validation on 2026-11-18: the mutation fixture passed with the whole runtime built
under Clang AddressSanitizer/UndefinedBehaviorSanitizer and leak detection. The
sanitized build used libstdc++; the installed libc++ reported an alloc/dealloc
mismatch in the pre-existing qualification probe's exception path. No sanitizer
category was disabled for the final fixture run. Leak detection required execution
outside the tracing sandbox. Normal runtime/publication checks used libc++.

Reproduce the instrumented build with a separate CMake/Ninja build directory,
`CMAKE_CXX_FLAGS=-fsanitize=address,undefined -fno-omit-frame-pointer -g0`,
`CMAKE_CXX_FLAGS_RELEASE=-O1 -DNDEBUG`, and target `collection_iteration_test`.
Run it with `ASAN_OPTIONS=detect_leaks=1 UBSAN_OPTIONS=halt_on_error=1`.

A focused four-family public-header consumer, compiled three times with Clang
O2/g0 and linked with mold against the prebuilt runtime, measured median native
wall 159.042 ms: compile wall 88.102 ms, frontend 31.613 ms, LLVM backend 15.006 ms,
link wall 68.691 ms. Medians are independent, so components need not sum exactly.
This is a native protocol fixture, not a compiler foreach or traversal-throughput
benchmark; there is no PHP compilation stage in these measurements.

## Construction-aware application outputs

Application-defined element traversal does not require default construction of an element.
The generated concrete output slot starts empty. Advancement selects and copies the value
(and requested key) before publishing the output and committing position. A throwing copy
leaves the previous output, key and position unchanged. No element-storage pointer escapes
the advance call. Exhaustion leaves outputs unchanged. Existing bounds, holes, mutation
and receiver-lifetime rules apply equally to reference outputs and construction-aware slots.
The slot owns its copied value through normal loop completion and suspended-cursor cleanup.

## Python indexed reads

A provider admits Python subscripting with `python_index_read`. It supplies key
validation, the result type, and the checked read. Vector and typed hash publish
this contract. Positional worker segments bind it directly and hash segments
reject indexed keys during validation. Boxed providers publish the
`python_index_read_snapshot` variant to keep the selected receiver alive by value
across an awaited key. The default string byte accessor stays limited to its
existing flavors. Indexed writes have their own admission rules.

## PHP array helpers

`array_map(callback, source)` maps one vector, typed hash or boxed table and
retains its keys. `array_filter(source, predicate)` retains matching entries in
encounter order. The source supplies an entry cursor and an associated result type.
The result builder creates an empty collection and inserts each retained value at
its original key. Vectors and one-dimensional fixed byte arrays use the positional
result family from metadata. For example, filtering `[10,20,30]` for `30` produces
one entry at key `2`. The default fixed-byte-array result is `hash<byte,int>`.
Typed hashes, mixed and dynamic use their own keyed result builders. Preparation
requires an available builder for the element type. Omitting the predicate uses the published scalar condition of each
element, including the false string `"0"`. Boxed tables require a callback.
The callback contracts are the same as for the general transforms above.

`in_array(needle, source[, strict])` uses the receiver’s published value cursor and
stops at its first match. This slice admits a comparable scalar needle of the
exact element type. Both flag values use the element's published equality. All
supplied operands, including a computed flag, evaluate once. Typed vectors, typed hashes and one-dimensional fixed byte arrays qualify. An indexed row can supply the byte array. Fixed arrays use their logical extent for both count and traversal, including extent zero. Boxed traversal supplies boxed values, which do not meet this helper’s exact scalar equality requirement.

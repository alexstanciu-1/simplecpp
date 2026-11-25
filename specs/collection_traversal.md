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
`foreach ($items as $key => &$item)` require a named, writable local that owns
a typed vector or hash. The element name is fresh and scoped to the loop. Each iteration
selects one vector index or live hash entry. Changing the optional key variable leaves the selection intact.

Reading the element reads the current slot. Assignments and supported compound
updates replace it immediately. Reads through another name for the vector see
the completed write, including before a break, return or exception. A by-value
capture takes the current element value. Shared handles retain object identity.
For inline records, edit a value copy and assign the whole element back.

The collection must keep its size, order and storage throughout the loop. Preparation
rejects append, removal, resize, reserve, reset, replacement and mutable reference
calls on the collection or a lexical alias. A closure with a mutable capture of that
storage cannot be invoked during the loop. By-value and read-only helper calls
remain eligible. The element cannot serve as reference storage for a call, local
alias, reference capture or segment grant. Ordinary indexed writes to existing
vector slots remain visible through the loop binding.

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

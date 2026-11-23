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

Mutation has no stable visitation promise: entries may shift, disappear, change or
be replaced. Bounds and hole checks prevent invalid slot access; no old entry
sequence is preserved. Independent loops have independent integer positions.
Concurrent mutation, escaping element references and by-reference foreach are not
supported. These methods do not make arbitrary dangling native references safe.

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

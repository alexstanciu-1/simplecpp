# Bounded value foreach

Doc Status: supporting

The [array contract](../../../specs/array_semantics.md#v02-value-iteration) owns semantics;
the [native protocol](../../specs/collection_traversal.md) owns storage safety.

PHP uses `foreach ($items as $key => $value) { ... }`; C++ and JS use
`foreach (items as key => value) { ... }`. The key is optional. Braces are required.
Reference bindings, destructuring, nullable unwrapping, keyed custom traversal and yield
are deferred. Value-only [Iterator<T> structs](iterator.md) are supported. Scalar/null
boxed sources throw, including on a later advance after replacement. Statically noniterable sources are preparation errors.

## Owners and facts

`ast_foreach_statement` owns the source expression, optional key, value binding,
body and header scope. It does not inherit the condition-bearing loop node or
invent a condition AST. `ast_iteration_binding` represents the produced value at
its authored name token. Its existing expression facts provide the unknown incoming
type for conversion; its declaration identity supplies storage only when introducing
a new local. Existing value destinations are referenced, not redeclared in the scope.
The statement retains only its resolved destination and packed conversion code.
There is no prepared statement list, cursor object or per-element analysis.

`Body_Collection` uses its existing scope owner and function-local storage-ID allocator.
The key is fresh in the header scope; a visible local/parameter value is reused,
otherwise the value is inferred in that scope. New names do not escape. Zero entries
leave an existing destination unchanged. The body has its usual child scope.

Preparation resolves the source once, queries its optional `iterable_type` capability,
settles bindings and ordinary assignment conversion, then visits the body once.
It uses the current loop-depth/completion rules and conservative write-cache barriers.
There is no fixed-point computation, collection scan or additional dependency table.
Existing calls and member access establish normal watches. Reset clears the new
binding facts alongside source/body facts; incremental recovery reuses unchanged ASTs.

## Generation and lowering

The API generator consumes each runtime publication's traversal plan and emits
key/value queries and native calls. Handwritten preparation and emission do not
switch on the four collection families. Mixed/dynamic values retain their own family;
boxed keys use mixed int/string values. A publication without mixed can still use
value-only dynamic traversal; keyed traversal receives an explicit provider diagnostic.

The emitter evaluates the source once. A named wrapper is borrowed, a temporary's
lifetime is extended, and the existing retained-place helper keeps the nearest shared
field owner alive. Generated collection bindings capture the initial physical limit;
custom Iterator methods need no extent. Both use native while.
For built-in collections, identity assignments write directly into the destination
through iteration_next, avoiding a second element copy. Conversion requires a separate
selected value. Custom methods stage existing destinations until next returns true.
Boxed keyed traversal decodes native integer/text transport through generated code;
value-only traversal creates no key transport. No lambda or runtime dispatch is added.

```cpp
auto&& iteration_source = var_items_0;
const auto iteration_limit = (iteration_source).iteration_limit();
scpp::int64 iteration_position = 0;
scpp::int64 var_value_1{};
while ((iteration_source).iteration_next(iteration_position, iteration_limit, var_value_1)) {
    // Native body; break/continue/return keep normal scoped cleanup.
}
```

## Proofs and measurements

`tests/foreach.php` proves all four families in all flavors, generated-code purity,
header scope, conversions, temporary sources, nested loops, transfers, growth,
replacement, clear, holes, independent string values and retained shared field owners.
Incremental checks keep the caller AST through provider edits, invalid return types,
deleted functions, repeated failed runs and recovery, comparing artifacts with fresh
compilation and checking no-op preparation counts. `foreach_native.py` runs the same
135 emitted fixtures and validates output and exit status.

On 2026-11-18, 42 selected mutation/lifetime consumers additionally passed ASan,
UBSan and leak detection against the fully instrumented runtime (libstdc++; see the
runtime protocol's installed-libc++ sanitizer qualification caveat). Other native
proofs use libc++. Runtime layouts agree between these configurations.

Native timings below are medians across each flavor's 45 correctness fixtures, not
repeated measurements of one program or a throughput comparison. O2/g0, Ninja8,
mold, no PCH/LTO, prebuilt runtime, fresh application objects. Direct protected Ninja
includes a qualification-cache subprocess. Trace stages overlap and do not sum to wall.
PHP times cover one source's frontend/preparation/lowering; the entire PHP fixture
suite including incremental/fresh checks took 615.48 ms.

| Flavor | PHP compiler ms | Native wall ms | Frontend ms | LLVM backend ms | Link ms |
| --- | ---: | ---: | ---: | ---: | ---: |
| C++ | 0.475 | 266.172 | 27.502 | 14.564 | 82 |
| PHP | 0.825 | 260.484 | 26.890 | 17.301 | 77 |
| JS | 0.820 | 271.777 | 30.775 | 17.949 | 81 |

Local raw fixtures, Ninja graphs, Clang traces and results: `v0.2/build/foreach-proof/`.
